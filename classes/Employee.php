<?php
/**
 * Employee Management Class
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';

class Employee {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new employee
     * @param array $employeeData
     * @return int|false
     */
    public function createEmployee($employeeData) {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($employeeData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Generate employee number if not provided
            if (empty($employeeData['employee_number'])) {
                $employeeData['employee_number'] = $this->generateEmployeeNumber();
            }
            
            // Check if employee number already exists
            if ($this->employeeNumberExists($employeeData['employee_number'])) {
                throw new Exception("Employee number already exists");
            }
            
            // Insert employee
            $sql = "INSERT INTO employees (user_id, employee_number, first_name, last_name, position, department, manager_id, hire_date, phone, address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $employeeId = insertRecord($sql, [
                $employeeData['user_id'] ?? null,
                $employeeData['employee_number'],
                $employeeData['first_name'],
                $employeeData['last_name'],
                $employeeData['position'] ?? null,
                $employeeData['department'] ?? null,
                $employeeData['manager_id'] ?? null,
                $employeeData['hire_date'] ?? null,
                $employeeData['phone'] ?? null,
                $employeeData['address'] ?? null
            ]);
            
            // Log employee creation
            logActivity($_SESSION['user_id'] ?? null, 'employee_created', 'employees', $employeeId, null, $employeeData);
            
            return $employeeId;
        } catch (Exception $e) {
            error_log("Create employee error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update employee
     * @param int $employeeId
     * @param array $employeeData
     * @return bool
     */
    public function updateEmployee($employeeId, $employeeData) {
        try {
            // Get current employee data for logging
            $currentEmployee = $this->getEmployeeById($employeeId);
            if (!$currentEmployee) {
                throw new Exception("Employee not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            $allowedFields = [
                'employee_number', 'first_name', 'last_name', 'position', 
                'department', 'manager_id', 'hire_date', 'phone', 'address', 'active'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $employeeData)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $employeeData[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            $params[] = $employeeId;
            $sql = "UPDATE employees SET " . implode(', ', $updateFields) . " WHERE employee_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            if ($affected > 0) {
                // Log employee update
                logActivity($_SESSION['user_id'] ?? null, 'employee_updated', 'employees', $employeeId, $currentEmployee, $employeeData);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update employee error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get employee by ID
     * @param int $employeeId
     * @return array|false
     */
    public function getEmployeeById($employeeId) {
        $sql = "SELECT e.*, u.username, u.email, u.role, 
                       m.first_name as manager_first_name, m.last_name as manager_last_name
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                LEFT JOIN employees m ON e.manager_id = m.employee_id 
                WHERE e.employee_id = ?";
        
        return fetchOne($sql, [$employeeId]);
    }
    
    /**
     * Get employee by user ID
     * @param int $userId
     * @return array|false
     */
    public function getEmployeeByUserId($userId) {
        $sql = "SELECT e.*, u.username, u.email, u.role, 
                       m.first_name as manager_first_name, m.last_name as manager_last_name
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                LEFT JOIN employees m ON e.manager_id = m.employee_id 
                WHERE e.user_id = ?";
        
        return fetchOne($sql, [$userId]);
    }
    
    /**
     * Get all employees with pagination and filtering
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getEmployees($page = 1, $limit = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE e.active = 1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $whereClause .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ? OR e.position LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, array_fill(0, 4, $searchTerm));
        }
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND e.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['manager_id'])) {
            $whereClause .= " AND e.manager_id = ?";
            $params[] = $filters['manager_id'];
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM employees e $whereClause";
        $totalResult = fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Get employees
        $sql = "SELECT e.*, u.username, u.email, u.role, 
                       m.first_name as manager_first_name, m.last_name as manager_last_name
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                LEFT JOIN employees m ON e.manager_id = m.employee_id 
                $whereClause 
                ORDER BY e.last_name, e.first_name 
                LIMIT $limit OFFSET $offset";
        
        $employees = fetchAll($sql, $params);
        
        return [
            'employees' => $employees,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Get employees managed by a specific manager
     * @param int $managerId
     * @return array
     */
    public function getTeamMembers($managerId) {
        $sql = "SELECT e.*, u.username, u.email 
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                WHERE e.manager_id = ? AND e.active = 1 
                ORDER BY e.last_name, e.first_name";
        
        return fetchAll($sql, [$managerId]);
    }
    
    /**
     * Get all departments
     * @return array
     */
    public function getDepartments() {
        $sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND active = 1 ORDER BY department";
        $result = fetchAll($sql);
        return array_column($result, 'department');
    }
    
    /**
     * Get all managers (employees who have direct reports)
     * @return array
     */
    public function getManagers() {
        $sql = "SELECT DISTINCT m.employee_id, m.first_name, m.last_name, m.position 
                FROM employees e 
                JOIN employees m ON e.manager_id = m.employee_id 
                WHERE e.active = 1 AND m.active = 1 
                ORDER BY m.last_name, m.first_name";
        
        return fetchAll($sql);
    }
    
    /**
     * Get potential managers (employees who can be assigned as managers)
     * @param int $excludeEmployeeId
     * @return array
     */
    public function getPotentialManagers($excludeEmployeeId = null) {
        $whereClause = "WHERE e.active = 1";
        $params = [];
        
        if ($excludeEmployeeId) {
            $whereClause .= " AND e.employee_id != ?";
            $params[] = $excludeEmployeeId;
        }
        
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position, e.department 
                FROM employees e 
                LEFT JOIN users u ON e.user_id = u.user_id 
                $whereClause 
                AND (u.role = 'manager' OR u.role = 'hr_admin')
                ORDER BY e.last_name, e.first_name";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get employee hierarchy (organizational chart data)
     * @param int $rootManagerId
     * @return array
     */
    public function getEmployeeHierarchy($rootManagerId = null) {
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.position, e.department, e.manager_id 
                FROM employees e 
                WHERE e.active = 1";
        
        $params = [];
        if ($rootManagerId) {
            $sql .= " AND (e.manager_id = ? OR e.employee_id = ?)";
            $params = [$rootManagerId, $rootManagerId];
        }
        
        $sql .= " ORDER BY e.manager_id, e.last_name, e.first_name";
        
        $employees = fetchAll($sql, $params);
        
        return $this->buildHierarchyTree($employees, $rootManagerId);
    }
    
    /**
     * Build hierarchical tree structure
     * @param array $employees
     * @param int $parentId
     * @return array
     */
    private function buildHierarchyTree($employees, $parentId = null) {
        $tree = [];
        
        foreach ($employees as $employee) {
            if ($employee['manager_id'] == $parentId) {
                $employee['children'] = $this->buildHierarchyTree($employees, $employee['employee_id']);
                $tree[] = $employee;
            }
        }
        
        return $tree;
    }
    
    /**
     * Generate unique employee number
     * @return string
     */
    private function generateEmployeeNumber() {
        $prefix = 'EMP';
        $year = date('Y');
        
        // Get the last employee number for this year
        $sql = "SELECT employee_number FROM employees 
                WHERE employee_number LIKE ? 
                ORDER BY employee_number DESC LIMIT 1";
        
        $pattern = $prefix . $year . '%';
        $result = fetchOne($sql, [$pattern]);
        
        if ($result) {
            // Extract the sequence number and increment
            $lastNumber = $result['employee_number'];
            $sequence = intval(substr($lastNumber, -3)) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if employee number exists
     * @param string $employeeNumber
     * @return bool
     */
    private function employeeNumberExists($employeeNumber) {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE employee_number = ?";
        $result = fetchOne($sql, [$employeeNumber]);
        return $result['count'] > 0;
    }
    
    /**
     * Delete employee (soft delete)
     * @param int $employeeId
     * @return bool
     */
    public function deleteEmployee($employeeId) {
        try {
            // Get current employee data for logging
            $employee = $this->getEmployeeById($employeeId);
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            // Soft delete by setting active = 0
            $sql = "UPDATE employees SET active = 0 WHERE employee_id = ?";
            $affected = updateRecord($sql, [$employeeId]);
            
            if ($affected > 0) {
                // Log employee deletion
                logActivity($_SESSION['user_id'] ?? null, 'employee_deleted', 'employees', $employeeId, $employee, ['active' => 0]);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete employee error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get employee statistics
     * @return array
     */
    public function getEmployeeStats() {
        $stats = [];
        
        // Total active employees
        $result = fetchOne("SELECT COUNT(*) as count FROM employees WHERE active = 1");
        $stats['total_employees'] = $result['count'];
        
        // Employees by department
        $result = fetchAll("SELECT department, COUNT(*) as count FROM employees WHERE active = 1 AND department IS NOT NULL GROUP BY department ORDER BY count DESC");
        $stats['by_department'] = $result;
        
        // Recent hires (last 30 days)
        $result = fetchOne("SELECT COUNT(*) as count FROM employees WHERE active = 1 AND hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['recent_hires'] = $result['count'];
        
        // Employees without managers
        $result = fetchOne("SELECT COUNT(*) as count FROM employees WHERE active = 1 AND manager_id IS NULL");
        $stats['without_managers'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Search employees
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchEmployees($query, $limit = 10) {
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_number, e.position, e.department 
                FROM employees e 
                WHERE e.active = 1 
                AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ? OR e.position LIKE ?) 
                ORDER BY e.last_name, e.first_name 
                LIMIT ?";
        
        $searchTerm = "%$query%";
        return fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
    }
}
?>