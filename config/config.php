<?php
/**
 * Application Configuration
 * Performance Evaluation System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Performance Evaluation System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/performance_evaluation_system');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Pagination settings
define('RECORDS_PER_PAGE', 20);

// Email settings (configure as needed)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@company.com');
define('FROM_NAME', 'Performance Evaluation System');

// Timezone
date_default_timezone_set('Europe/Madrid');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/database.php';

/**
 * Get application setting from database
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getAppSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $settings = [];
        try {
            $result = fetchAll("SELECT setting_key, setting_value FROM system_settings");
            foreach ($result as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Failed to load app settings: " . $e->getMessage());
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Set application setting in database
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setAppSetting($key, $value) {
    try {
        $sql = "INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        executeQuery($sql, [$key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to set app setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure password hash
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log user activity
 * @param int $userId
 * @param string $action
 * @param string $tableName
 * @param int $recordId
 * @param array $oldValues
 * @param array $newValues
 */
function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        executeQuery($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Get user's full name
 * @param array $user
 * @return string
 */
function getFullName($user) {
    if (isset($user['first_name']) && isset($user['last_name'])) {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }
    return $user['username'] ?? 'Unknown User';
}

/**
 * Check if user has permission
 * @param string $permission
 * @param string $userRole
 * @return bool
 */
function hasPermission($permission, $userRole = null) {
    if (!$userRole && isset($_SESSION['user_role'])) {
        $userRole = $_SESSION['user_role'];
    }
    
    $permissions = [
        'hr_admin' => ['*'], // Full access
        'manager' => [
            'view_own_team',
            'create_evaluation',
            'edit_evaluation',
            'view_evaluation',
            'view_reports'
        ],
        'employee' => [
            'view_own_evaluation',
            'view_own_profile'
        ]
    ];
    
    if (!isset($permissions[$userRole])) {
        return false;
    }
    
    return in_array('*', $permissions[$userRole]) || in_array($permission, $permissions[$userRole]);
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message
 * @param string $message
 * @param string $type
 */
function setFlashMessage($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}
?>