<?php
/**
 * Department Management Class
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';

class Department {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Get all active departments
     * @return array
     */
    public function getDepartments() {
        $sql = "SELECT d.*, 
                       CONCAT(e.first_name, ' ', e.last_name) as manager_name,
                       e.employee_id as manager_employee_id
                FROM departments d 
                LEFT JOIN employees e ON d.manager_id = e.employee_id 
                WHERE d.is_active = 1 
                ORDER BY d.department_name";
        return fetchAll($sql);
    }
    
    /**
     * Get all departments (including inactive)
     * @return array
     */
    public function getAllDepartments() {
        $sql = "SELECT d.*, 
                       CONCAT(e.first_name, ' ', e.last_name) as manager_name,
                       e.employee_id as manager_employee_id
                FROM departments d 
                LEFT JOIN employees e ON d.manager_id = e.employee_id 
                ORDER BY d.is_active DESC, d.department_name";
        return fetchAll($sql);
    }
    
    /**
     * Get department by ID
     * @param int $id
     * @return array|false
     */
    public function getDepartmentById($id) {
        $sql = "SELECT * FROM departments WHERE id = ?";
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Create new department
     * @param array $data
     * @return int|false
     */
    public function createDepartment($data) {
        $sql = "INSERT INTO departments (department_name, description, manager_id, created_by) VALUES (?, ?, ?, ?)";
        return insertRecord($sql, [
            $data['department_name'],
            $data['description'] ?? null,
            $data['manager_id'] ?? null,
            $data['created_by'] ?? null
        ]);
    }
    
    /**
     * Update department
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateDepartment($id, $data) {
        $sql = "UPDATE departments SET department_name = ?, description = ?, manager_id = ? WHERE id = ?";
        return updateRecord($sql, [
            $data['department_name'],
            $data['description'] ?? null,
            $data['manager_id'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete department (soft delete)
     * @param int $id
     * @return int
     */
    public function deleteDepartment($id) {
        $sql = "UPDATE departments SET is_active = 0 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Restore department
     * @param int $id
     * @return int
     */
    public function restoreDepartment($id) {
        $sql = "UPDATE departments SET is_active = 1 WHERE id = ?";
        return updateRecord($sql, [$id]);
    }
    
    /**
     * Get available managers (active employees)
     * @return array
     */
    public function getAvailableManagers() {
        $sql = "SELECT employee_id, CONCAT(first_name, ' ', last_name) as full_name, position, department 
                FROM employees 
                WHERE active = 1 
                ORDER BY first_name, last_name";
        return fetchAll($sql);
    }
    
    /**
     * Get department statistics
     * @return array
     */
    public function getDepartmentStats() {
        $stats = [];
        
        // Total departments
        $result = fetchOne("SELECT COUNT(*) as count FROM departments WHERE is_active = 1");
        $stats['total_departments'] = $result['count'];
        
        // Departments with employees
        $result = fetchOne("SELECT COUNT(DISTINCT department) as count FROM employees WHERE active = 1 AND department IS NOT NULL");
        $stats['departments_with_employees'] = $result['count'];
        
        return $stats;
    }
}
?>
