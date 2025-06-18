<?php
/**
 * User Management Class
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Authenticate user login
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function login($username, $password) {
        try {
            // Check for too many failed attempts
            if ($this->isAccountLocked($username)) {
                return ['error' => 'Account temporarily locked due to too many failed attempts'];
            }
            
            $sql = "SELECT u.*, e.first_name, e.last_name, e.employee_id 
                    FROM users u 
                    LEFT JOIN employees e ON u.user_id = e.user_id 
                    WHERE u.username = ? AND u.is_active = 1";
            
            $user = fetchOne($sql, [$username]);
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Clear failed attempts
                $this->clearFailedAttempts($username);
                
                // Update last login
                $this->updateLastLogin($user['user_id']);
                
                // Set session variables
                $this->setUserSession($user);
                
                // Log successful login
                logActivity($user['user_id'], 'login_success');
                
                return $user;
            } else {
                // Record failed attempt
                $this->recordFailedAttempt($username);
                
                // Log failed login attempt
                if ($user) {
                    logActivity($user['user_id'], 'login_failed');
                }
                
                return false;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout');
        }
        
        // Destroy session
        session_destroy();
        session_start();
        
        // Regenerate session ID
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->isSessionValid();
    }
    
    /**
     * Check if session is valid
     * @return bool
     */
    private function isSessionValid() {
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }
        
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Set user session variables
     * @param array $user
     */
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['full_name'] = getFullName($user);
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Get current user information
     * @return array|false
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $sql = "SELECT u.*, e.first_name, e.last_name, e.employee_id, e.position, e.department 
                FROM users u 
                LEFT JOIN employees e ON u.user_id = e.user_id 
                WHERE u.user_id = ?";
        
        return fetchOne($sql, [$_SESSION['user_id']]);
    }
    
    /**
     * Create new user
     * @param array $userData
     * @return int|false
     */
    public function createUser($userData) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Validate email
            if (!isValidEmail($userData['email'])) {
                throw new Exception("Invalid email address");
            }
            
            // Validate password
            if (strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
                throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters long");
            }
            
            // Check if username or email already exists
            if ($this->userExists($userData['username'], $userData['email'])) {
                throw new Exception("Username or email already exists");
            }
            
            // Hash password
            $passwordHash = hashPassword($userData['password']);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $userId = insertRecord($sql, [
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['role']
            ]);
            
            // Log user creation
            logActivity($_SESSION['user_id'] ?? null, 'user_created', 'users', $userId, null, [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]);
            
            return $userId;
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user
     * @param int $userId
     * @param array $userData
     * @return bool
     */
    public function updateUser($userId, $userData) {
        try {
            // Get current user data for logging
            $currentUser = $this->getUserById($userId);
            if (!$currentUser) {
                throw new Exception("User not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            if (!empty($userData['username'])) {
                $updateFields[] = "username = ?";
                $params[] = $userData['username'];
            }
            
            if (!empty($userData['email'])) {
                if (!isValidEmail($userData['email'])) {
                    throw new Exception("Invalid email address");
                }
                $updateFields[] = "email = ?";
                $params[] = $userData['email'];
            }
            
            if (!empty($userData['password'])) {
                if (strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
                    throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters long");
                }
                $updateFields[] = "password_hash = ?";
                $params[] = hashPassword($userData['password']);
            }
            
            if (isset($userData['role'])) {
                $updateFields[] = "role = ?";
                $params[] = $userData['role'];
            }
            
            if (isset($userData['is_active'])) {
                $updateFields[] = "is_active = ?";
                $params[] = $userData['is_active'];
            }
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            if ($affected > 0) {
                // Log user update
                logActivity($_SESSION['user_id'] ?? null, 'user_updated', 'users', $userId, $currentUser, $userData);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|false
     */
    public function getUserById($userId) {
        $sql = "SELECT u.*, e.first_name, e.last_name, e.employee_id, e.position, e.department 
                FROM users u 
                LEFT JOIN employees e ON u.user_id = e.user_id 
                WHERE u.user_id = ?";
        
        return fetchOne($sql, [$userId]);
    }
    
    /**
     * Get all users with pagination
     * @param int $page
     * @param int $limit
     * @param string $search
     * @return array
     */
    public function getUsers($page = 1, $limit = RECORDS_PER_PAGE, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_fill(0, 4, $searchTerm);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u LEFT JOIN employees e ON u.user_id = e.user_id $whereClause";
        $totalResult = fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Get users
        $sql = "SELECT u.*, e.first_name, e.last_name, e.position, e.department 
                FROM users u 
                LEFT JOIN employees e ON u.user_id = e.user_id 
                $whereClause 
                ORDER BY u.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $users = fetchAll($sql, $params);
        
        return [
            'users' => $users,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Check if user exists
     * @param string $username
     * @param string $email
     * @return bool
     */
    private function userExists($username, $email) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?";
        $result = fetchOne($sql, [$username, $email]);
        return $result['count'] > 0;
    }
    
    /**
     * Record failed login attempt
     * @param string $username
     */
    private function recordFailedAttempt($username) {
        $key = "failed_attempts_$username";
        $attempts = $_SESSION[$key] ?? 0;
        $_SESSION[$key] = $attempts + 1;
        $_SESSION["last_attempt_$username"] = time();
    }
    
    /**
     * Clear failed login attempts
     * @param string $username
     */
    private function clearFailedAttempts($username) {
        unset($_SESSION["failed_attempts_$username"]);
        unset($_SESSION["last_attempt_$username"]);
    }
    
    /**
     * Check if account is locked
     * @param string $username
     * @return bool
     */
    private function isAccountLocked($username) {
        $attempts = $_SESSION["failed_attempts_$username"] ?? 0;
        $lastAttempt = $_SESSION["last_attempt_$username"] ?? 0;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            if (time() - $lastAttempt < LOGIN_LOCKOUT_TIME) {
                return true;
            } else {
                // Lockout period expired, clear attempts
                $this->clearFailedAttempts($username);
            }
        }
        
        return false;
    }
    
    /**
     * Update last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        executeQuery($sql, [$userId]);
    }
    
    /**
     * Change user password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Verify current password
            if (!verifyPassword($currentPassword, $user['password_hash'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                throw new Exception("New password must be at least " . PASSWORD_MIN_LENGTH . " characters long");
            }
            
            // Update password
            $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $affected = updateRecord($sql, [hashPassword($newPassword), $userId]);
            
            if ($affected > 0) {
                logActivity($userId, 'password_changed');
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>