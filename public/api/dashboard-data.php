<?php
/**
 * Dashboard Data API Endpoint
 * Phase 2: Dashboard & Analytics Implementation
 * Provides JSON data for dashboard visualizations and analytics
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/DashboardAnalytics.php';

// Require authentication
requireAuth();

// Set JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Get request parameters
    $dashboardType = $_GET['type'] ?? 'manager';
    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $managerId = isset($_GET['manager_id']) ? (int)$_GET['manager_id'] : null;
    $periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
    $department = $_GET['department'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Build filters array
    $filters = [];
    if ($periodId) $filters['period_id'] = $periodId;
    if ($department) $filters['department'] = $department;
    if ($startDate) $filters['start_date'] = $startDate;
    if ($endDate) $filters['end_date'] = $endDate;
    
    // Initialize analytics class
    $analytics = new DashboardAnalytics();
    
    // Get current user info for authorization
    $currentUser = getCurrentUser();
    $userRole = $_SESSION['user_role'];
    $currentEmployeeId = $_SESSION['employee_id'] ?? null;
    
    // Route to appropriate dashboard data based on type and user permissions
    switch ($dashboardType) {
        case 'manager':
            // Verify manager permissions
            if ($userRole !== 'manager' && $userRole !== 'hr_admin') {
                throw new Exception('Insufficient permissions for manager dashboard');
            }
            
            // Use provided manager ID or current user's employee ID
            $targetManagerId = $managerId ?? $currentEmployeeId;
            if (!$targetManagerId) {
                throw new Exception('Manager ID not found');
            }
            
            // HR Admin can view any manager's dashboard, managers can only view their own
            if ($userRole === 'manager' && $targetManagerId != $currentEmployeeId) {
                throw new Exception('Managers can only view their own dashboard');
            }
            
            $data = $analytics->getManagerDashboardData($targetManagerId, $filters);
            break;
            
        case 'employee':
            // Verify employee permissions
            if ($userRole !== 'employee' && $userRole !== 'manager' && $userRole !== 'hr_admin') {
                throw new Exception('Insufficient permissions for employee dashboard');
            }
            
            // Use provided employee ID or current user's employee ID
            $targetEmployeeId = $employeeId ?? $currentEmployeeId;
            if (!$targetEmployeeId) {
                throw new Exception('Employee ID not found');
            }
            
            // Check if user can access this employee's data
            if (!canAccessEmployee($targetEmployeeId)) {
                throw new Exception('Cannot access employee data');
            }
            
            $data = $analytics->getEmployeeDashboardData($targetEmployeeId, $filters);
            break;
            
        case 'hr':
            // Verify HR admin permissions
            if ($userRole !== 'hr_admin') {
                throw new Exception('HR Admin permissions required');
            }
            
            $data = $analytics->getHRAnalyticsDashboard($filters);
            break;
            
        case 'chart_data':
            // Handle specific chart data requests
            $chartType = $_GET['chart'] ?? '';
            $data = getChartData($chartType, $analytics, $filters, $userRole, $currentEmployeeId);
            break;
            
        default:
            throw new Exception('Invalid dashboard type');
    }
    
    // Log API access for monitoring
    logActivity($_SESSION['user_id'], 'dashboard_api_access', 'dashboard_data', null, null, [
        'dashboard_type' => $dashboardType,
        'filters' => $filters,
        'execution_time' => $data['execution_time'] ?? 0
    ]);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('c'),
        'user_role' => $userRole
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Dashboard API error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}

/**
 * Get specific chart data based on chart type
 * @param string $chartType
 * @param DashboardAnalytics $analytics
 * @param array $filters
 * @param string $userRole
 * @param int $currentEmployeeId
 * @return array
 */
function getChartData(string $chartType, DashboardAnalytics $analytics, array $filters, string $userRole, ?int $currentEmployeeId): array {
    switch ($chartType) {
        case 'evidence_trends':
            if ($userRole === 'manager') {
                $data = $analytics->getManagerDashboardData($currentEmployeeId, $filters);
                return $data['evidence_trends'] ?? [];
            } elseif ($userRole === 'employee') {
                $data = $analytics->getEmployeeDashboardData($currentEmployeeId, $filters);
                return $data['evidence_history'] ?? [];
            } elseif ($userRole === 'hr_admin') {
                $data = $analytics->getHRAnalyticsDashboard($filters);
                return $data['organizational_patterns'] ?? [];
            }
            break;
            
        case 'performance_distribution':
            if ($userRole === 'manager') {
                $data = $analytics->getManagerDashboardData($currentEmployeeId, $filters);
                return $data['performance_insights'] ?? [];
            } elseif ($userRole === 'hr_admin') {
                $data = $analytics->getHRAnalyticsDashboard($filters);
                return $data['performance_distribution'] ?? [];
            }
            break;
            
        case 'department_comparison':
            if ($userRole === 'hr_admin') {
                $data = $analytics->getHRAnalyticsDashboard($filters);
                return $data['department_comparison'] ?? [];
            }
            break;
            
        case 'team_comparison':
            if ($userRole === 'manager') {
                $data = $analytics->getManagerDashboardData($currentEmployeeId, $filters);
                return $data['team_comparison'] ?? [];
            }
            break;
            
        case 'personal_trends':
            if ($userRole === 'employee') {
                $data = $analytics->getEmployeeDashboardData($currentEmployeeId, $filters);
                return $data['performance_trends'] ?? [];
            }
            break;
            
        case 'coaching_opportunities':
            if ($userRole === 'manager') {
                $data = $analytics->getManagerDashboardData($currentEmployeeId, $filters);
                return $data['coaching_opportunities'] ?? [];
            }
            break;
            
        case 'usage_analytics':
            if ($userRole === 'hr_admin') {
                $data = $analytics->getHRAnalyticsDashboard($filters);
                return $data['usage_analytics'] ?? [];
            }
            break;
            
        default:
            throw new Exception('Invalid chart type');
    }
    
    throw new Exception('Chart data not available for current user role');
}

/**
 * Export dashboard data (for reports)
 */
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    // Handle export functionality
    $format = $_GET['format'] ?? 'json';
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dashboard_data_' . date('Y-m-d') . '.csv"');
        
        // Convert data to CSV format
        // This would need to be implemented based on specific export requirements
        echo "Export functionality not yet implemented for CSV format";
    } else {
        // Default JSON export
        header('Content-Disposition: attachment; filename="dashboard_data_' . date('Y-m-d') . '.json"');
    }
}
?>