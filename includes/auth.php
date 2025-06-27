<?php
/**
 * Authentication Functions
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    $user = new User();
    return $user->isLoggedIn();
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $redirectUrl
 */
function requireAuth($redirectUrl = '/login.php') {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect($redirectUrl);
    }
}

/**
 * Require specific role
 * @param string|array $requiredRoles
 * @param string $redirectUrl
 */
function requireRole($requiredRoles, $redirectUrl = '/dashboard.php') {
    requireAuth();
    
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    
    if (!in_array($userRole, $requiredRoles)) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        redirect($redirectUrl);
    }
}

/**
 * Check if user has specific permission
 * @param string $permission
 * @return bool
 */
function checkPermission($permission) {
    if (!isAuthenticated()) {
        return false;
    }
    
    return hasPermission($permission, $_SESSION['user_role']);
}

/**
 * Get current user information
 * @return array|false
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return false;
    }
    
    $user = new User();
    return $user->getCurrentUser();
}

/**
 * Check if current user can access employee data
 * @param int $employeeId
 * @return bool
 */
function canAccessEmployee($employeeId) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    // HR Admin can access all employees
    if ($userRole === 'hr_admin') {
        return true;
    }
    
    // Employees can only access their own data
    if ($userRole === 'employee') {
        return $_SESSION['employee_id'] == $employeeId;
    }
    
    // Managers can access their direct reports
    if ($userRole === 'manager') {
        require_once __DIR__ . '/../classes/Employee.php';
        $employeeClass = new Employee();
        $teamMembers = $employeeClass->getTeamMembers($_SESSION['employee_id']);
        
        foreach ($teamMembers as $member) {
            if ($member['employee_id'] == $employeeId) {
                return true;
            }
        }
        
        // Managers can also access their own data
        return $_SESSION['employee_id'] == $employeeId;
    }
    
    return false;
}

/**
 * Check if current user can access evaluation (ENHANCED with direct manager relationship)
 * @param array $evaluation
 * @return bool
 */
function canAccessEvaluation($evaluation) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    $employeeId = $_SESSION['employee_id'] ?? null;
    
    // HR Admin can access all evaluations
    if ($userRole === 'hr_admin') {
        return true;
    }
    
    // Evaluator can access evaluations they created
    if ($evaluation['evaluator_id'] == $userId) {
        return true;
    }
    
    // Employee can access their own evaluations
    if ($userRole === 'employee' && $evaluation['employee_id'] == $employeeId) {
        return true;
    }
    
    // CRITICAL FIX: Manager can access evaluations via direct manager_id relationship
    if ($userRole === 'manager' && $evaluation['manager_id'] == $employeeId) {
        return true;
    }
    
    // FALLBACK: Use old logic for evaluations without manager_id (backward compatibility)
    if ($userRole === 'manager' && empty($evaluation['manager_id'])) {
        return canAccessEmployee($evaluation['employee_id']);
    }
    
    return false;
}

/**
 * Check if current user can edit evaluation (ENHANCED with proper workflow state management)
 * @param array $evaluation
 * @return bool
 */
function canEditEvaluation($evaluation) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    $employeeId = $_SESSION['employee_id'] ?? null;
    $status = $evaluation['status'];
    
    // WORKFLOW STATE MANAGEMENT: Respect evaluation lifecycle
    switch ($status) {
        case 'draft':
            // Draft evaluations can be edited by manager, evaluator, or HR admin
            break;
            
        case 'submitted':
            // Submitted evaluations can only be reviewed/edited by HR admin
            if ($userRole !== 'hr_admin') {
                return false;
            }
            break;
            
        case 'reviewed':
        case 'approved':
        case 'rejected':
            // Final states - only HR admin can make changes
            if ($userRole !== 'hr_admin') {
                return false;
            }
            break;
            
        default:
            return false;
    }
    
    // HR Admin can edit evaluations in any state (for review/approval workflow)
    if ($userRole === 'hr_admin') {
        return true;
    }
    
    // For draft evaluations: Manager can edit evaluations for their direct reports
    if ($userRole === 'manager' && $evaluation['manager_id'] == $employeeId) {
        return true;
    }
    
    // For draft evaluations: Evaluator can edit their evaluations
    if ($evaluation['evaluator_id'] == $userId) {
        return true;
    }
    
    // FALLBACK: Use old logic for evaluations without manager_id (backward compatibility)
    if ($userRole === 'manager' && empty($evaluation['manager_id'])) {
        return canAccessEmployee($evaluation['employee_id']);
    }
    
    return false;
}

/**
 * Get user's accessible employees
 * @return array
 */
function getAccessibleEmployees() {
    if (!isAuthenticated()) {
        return [];
    }
    
    require_once __DIR__ . '/../classes/Employee.php';
    $employeeClass = new Employee();
    
    $userRole = $_SESSION['user_role'];
    
    // HR Admin can access all employees
    if ($userRole === 'hr_admin') {
        $result = $employeeClass->getEmployees(1, 1000); // Get all employees
        return $result['employees'];
    }
    
    // Managers can access their direct reports
    if ($userRole === 'manager') {
        return $employeeClass->getTeamMembers($_SESSION['employee_id']);
    }
    
    // Employees can only access themselves
    if ($userRole === 'employee') {
        $employee = $employeeClass->getEmployeeById($_SESSION['employee_id']);
        return $employee ? [$employee] : [];
    }
    
    return [];
}

/**
 * Generate and verify CSRF token for forms
 * @return string
 */
function csrf_token() {
    return generateCSRFToken();
}

/**
 * Verify CSRF token from form submission
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    return verifyCSRFToken($token);
}

/**
 * Protect form submission with CSRF check
 */
function protect_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard.php');
        }
    }
}

/**
 * Check if user account is active
 * @return bool
 */
function isAccountActive() {
    if (!isAuthenticated()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user && $user['is_active'];
}

/**
 * Logout current user
 */
function logout() {
    $user = new User();
    $user->logout();
}

/**
 * Get user's full name for display
 * @return string
 */
function getUserDisplayName() {
    if (!isAuthenticated()) {
        return 'Guest';
    }
    
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
}

/**
 * Get user's role display name
 * @return string
 */
function getUserRoleDisplayName() {
    if (!isAuthenticated()) {
        return '';
    }
    
    $role = $_SESSION['user_role'] ?? '';
    
    switch ($role) {
        case 'hr_admin':
            return 'HR Administrator';
        case 'manager':
            return 'Manager';
        case 'employee':
            return 'Employee';
        default:
            return ucfirst($role);
    }
}

/**
 * Check if current user is HR Admin
 * @return bool
 */
function isHRAdmin() {
    return isAuthenticated() && $_SESSION['user_role'] === 'hr_admin';
}

/**
 * Check if current user is Manager
 * @return bool
 */
function isManager() {
    return isAuthenticated() && $_SESSION['user_role'] === 'manager';
}

/**
 * Check if current user is Employee
 * @return bool
 */
function isEmployee() {
    return isAuthenticated() && $_SESSION['user_role'] === 'employee';
}

/**
 * Get navigation menu items based on user role
 * @return array
 */
function getNavigationMenu() {
    if (!isAuthenticated()) {
        return [];
    }
    
    $menu = [];
    $userRole = $_SESSION['user_role'];
    
    // Dashboard - available to all authenticated users
    $menu[] = [
        'title' => 'Dashboard',
        'url' => '/dashboard.php',
        'icon' => 'fas fa-tachometer-alt'
    ];
    
    // Evaluations menu
    if ($userRole === 'hr_admin' || $userRole === 'manager') {
        $menu[] = [
            'title' => 'Evaluations',
            'url' => '/evaluation/',
            'icon' => 'fas fa-clipboard-list',
            'submenu' => [
                ['title' => 'All Evaluations', 'url' => '/evaluation/list.php'],
                ['title' => 'Create Evaluation', 'url' => '/evaluation/create.php'],
                ['title' => 'My Evaluations', 'url' => '/evaluation/my-evaluations.php']
            ]
        ];
    } elseif ($userRole === 'employee') {
        $menu[] = [
            'title' => 'My Evaluations',
            'url' => '/evaluation/my-evaluations.php',
            'icon' => 'fas fa-clipboard-list'
        ];
    }
    
    // Employees menu (HR Admin and Managers)
    if ($userRole === 'hr_admin' || $userRole === 'manager') {
        $menu[] = [
            'title' => 'Employees',
            'url' => '/employees/',
            'icon' => 'fas fa-users',
            'submenu' => $userRole === 'hr_admin' ? [
                ['title' => 'All Employees', 'url' => '/employees/list.php'],
                ['title' => 'Add Employee', 'url' => '/employees/create.php'],
                ['title' => 'Organization Chart', 'url' => '/employees/hierarchy.php']
            ] : [
                ['title' => 'My Team', 'url' => '/employees/team.php']
            ]
        ];
    }
    
    // Administration menu (HR Admin only)
    if ($userRole === 'hr_admin') {
        $menu[] = [
            'title' => 'Administration',
            'url' => '/admin/',
            'icon' => 'fas fa-cog',
            'submenu' => [
                ['title' => 'Users', 'url' => '/admin/users.php'],
                ['title' => 'Evaluation Periods', 'url' => '/admin/periods.php'],
                ['title' => 'System Settings', 'url' => '/admin/settings.php'],
                ['title' => 'Audit Log', 'url' => '/admin/audit.php']
            ]
        ];
    }
    
    // Reports menu
    if ($userRole === 'hr_admin' || $userRole === 'manager') {
        $menu[] = [
            'title' => 'Reports',
            'url' => '/reports/',
            'icon' => 'fas fa-chart-bar',
            'submenu' => [
                ['title' => 'Performance Reports', 'url' => '/reports/performance.php'],
                ['title' => 'Department Reports', 'url' => '/reports/department.php'],
                ['title' => 'Period Reports', 'url' => '/reports/period.php']
            ]
        ];
    }
    
    return $menu;
}
?>