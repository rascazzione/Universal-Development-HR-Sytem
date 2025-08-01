<?php
/**
 * Reports API Endpoints
 * Phase 3: Advanced Features - Reporting System
 * Growth Evidence System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/ReportGenerator.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    $reportGenerator = new ReportGenerator();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($reportGenerator, $action);
            break;
            
        case 'POST':
            handlePostRequest($reportGenerator, $action);
            break;
            
        case 'PUT':
            handlePutRequest($reportGenerator, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($reportGenerator, $action);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($reportGenerator, $action) {
    switch ($action) {
        case 'generate':
            $reportType = $_GET['type'] ?? '';
            $parameters = $_GET;
            unset($parameters['action'], $parameters['type']);
            
            // Validate permissions
            validateReportPermissions($reportType, $parameters);
            
            $reportData = $reportGenerator->generateReportByType($reportType, $parameters);
            
            echo json_encode([
                'success' => true,
                'report' => $reportData
            ]);
            break;
            
        case 'export':
            $reportType = $_GET['type'] ?? '';
            $format = $_GET['format'] ?? 'pdf';
            $parameters = $_GET;
            unset($parameters['action'], $parameters['type'], $parameters['format']);
            
            // Validate permissions
            validateReportPermissions($reportType, $parameters);
            
            $reportData = $reportGenerator->generateReportByType($reportType, $parameters);
            
            if ($format === 'pdf') {
                $filePath = $reportGenerator->exportToPDF($reportData);
            } else {
                $filePath = $reportGenerator->exportToExcel($reportData);
            }
            
            echo json_encode([
                'success' => true,
                'file_path' => $filePath,
                'download_url' => "/api/reports.php?action=download&file=" . urlencode(basename($filePath))
            ]);
            break;
            
        case 'download':
            $filename = $_GET['file'] ?? '';
            $filePath = 'uploads/reports/' . basename($filename);
            
            if (!file_exists($filePath)) {
                throw new Exception('File not found');
            }
            
            // Set appropriate headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            exit;
            break;
            
        case 'scheduled':
            // Only allow HR admins and managers to view scheduled reports
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Filter by creator if not HR admin
            if ($_SESSION['role'] !== 'hr_admin') {
                $whereClause .= " AND created_by = ?";
                $params[] = $_SESSION['user_id'];
            }
            
            $sql = "SELECT * FROM scheduled_reports $whereClause ORDER BY created_at DESC";
            $scheduledReports = fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'scheduled_reports' => $scheduledReports
            ]);
            break;
            
        case 'history':
            // Only allow HR admins and managers to view report history
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Filter by generator if not HR admin
            if ($_SESSION['role'] !== 'hr_admin') {
                $whereClause .= " AND generated_by = ?";
                $params[] = $_SESSION['user_id'];
            }
            
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = max(0, intval($_GET['offset'] ?? 0));
            
            $sql = "SELECT * FROM report_history $whereClause ORDER BY generated_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $reportHistory = fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'report_history' => $reportHistory,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($reportHistory) === $limit
                ]
            ]);
            break;
            
        case 'templates':
            // Get available report templates
            $templates = [
                'evidence_summary' => [
                    'name' => 'Evidence Summary Report',
                    'description' => 'Comprehensive overview of evidence entries with statistics and trends',
                    'parameters' => ['start_date', 'end_date', 'employee_id', 'manager_id', 'dimension']
                ],
                'performance_trends' => [
                    'name' => 'Performance Trends Report',
                    'description' => 'Analysis of performance trends over time for specific employees',
                    'parameters' => ['employee_id', 'start_date', 'end_date']
                ],
                'manager_overview' => [
                    'name' => 'Manager Overview Report',
                    'description' => 'Team performance overview for managers',
                    'parameters' => ['manager_id', 'start_date', 'end_date']
                ],
                'custom' => [
                    'name' => 'Custom Report',
                    'description' => 'Build your own custom report with specific criteria',
                    'parameters' => ['custom_query', 'filters', 'aggregations']
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'templates' => $templates
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($reportGenerator, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'schedule':
            // Only allow HR admins and managers to schedule reports
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['report_name', 'report_type', 'parameters', 'recipients', 'schedule_frequency'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $scheduleData = [
                'report_name' => $input['report_name'],
                'report_type' => $input['report_type'],
                'parameters' => $input['parameters'],
                'recipients' => $input['recipients'],
                'schedule_frequency' => $input['schedule_frequency'],
                'schedule_day_of_week' => $input['schedule_day_of_week'] ?? null,
                'schedule_day_of_month' => $input['schedule_day_of_month'] ?? null,
                'created_by' => $_SESSION['user_id']
            ];
            
            $scheduleId = $reportGenerator->scheduleReport($scheduleData);
            
            echo json_encode([
                'success' => true,
                'schedule_id' => $scheduleId
            ]);
            break;
            
        case 'generate_custom':
            // Only allow HR admins and managers to generate custom reports
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $parameters = $input['parameters'] ?? [];
            $reportData = $reportGenerator->generateCustomReport($parameters);
            
            echo json_encode([
                'success' => true,
                'report' => $reportData
            ]);
            break;
            
        case 'process_scheduled':
            // Only allow system/HR admin to process scheduled reports
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $results = $reportGenerator->processScheduledReports();
            
            echo json_encode([
                'success' => true,
                'results' => $results
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($reportGenerator, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_schedule':
            // Only allow HR admins and managers to update scheduled reports
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $scheduleId = $input['schedule_id'] ?? 0;
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
            // Check if user owns this schedule or is HR admin
            $schedule = fetchOne("SELECT * FROM scheduled_reports WHERE schedule_id = ?", [$scheduleId]);
            if (!$schedule) {
                throw new Exception('Schedule not found');
            }
            
            if ($_SESSION['role'] !== 'hr_admin' && $schedule['created_by'] != $_SESSION['user_id']) {
                throw new Exception('Insufficient permissions');
            }
            
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['report_name', 'parameters', 'recipients', 'schedule_frequency', 'schedule_day_of_week', 'schedule_day_of_month', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $input)) {
                    $updateFields[] = "$field = ?";
                    $params[] = is_array($input[$field]) ? json_encode($input[$field]) : $input[$field];
                }
            }
            
            if (!empty($updateFields)) {
                $params[] = $scheduleId;
                $sql = "UPDATE scheduled_reports SET " . implode(', ', $updateFields) . " WHERE schedule_id = ?";
                updateRecord($sql, $params);
            }
            
            echo json_encode([
                'success' => true
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($reportGenerator, $action) {
    switch ($action) {
        case 'schedule':
            // Only allow HR admins and managers to delete scheduled reports
            if (!in_array($_SESSION['role'], ['hr_admin', 'manager'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $scheduleId = $_GET['schedule_id'] ?? 0;
            if (!$scheduleId) {
                throw new Exception('Schedule ID is required');
            }
            
            // Check if user owns this schedule or is HR admin
            $schedule = fetchOne("SELECT * FROM scheduled_reports WHERE schedule_id = ?", [$scheduleId]);
            if (!$schedule) {
                throw new Exception('Schedule not found');
            }
            
            if ($_SESSION['role'] !== 'hr_admin' && $schedule['created_by'] != $_SESSION['user_id']) {
                throw new Exception('Insufficient permissions');
            }
            
            $sql = "DELETE FROM scheduled_reports WHERE schedule_id = ?";
            $affected = updateRecord($sql, [$scheduleId]);
            
            echo json_encode([
                'success' => $affected > 0
            ]);
            break;
            
        case 'history':
            // Only allow HR admins to delete report history
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $historyId = $_GET['history_id'] ?? 0;
            if (!$historyId) {
                throw new Exception('History ID is required');
            }
            
            // Get file path to delete file
            $history = fetchOne("SELECT file_path FROM report_history WHERE history_id = ?", [$historyId]);
            if ($history && $history['file_path'] && file_exists($history['file_path'])) {
                unlink($history['file_path']);
            }
            
            $sql = "DELETE FROM report_history WHERE history_id = ?";
            $affected = updateRecord($sql, [$historyId]);
            
            echo json_encode([
                'success' => $affected > 0
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Validate report permissions based on user role and parameters
 */
function validateReportPermissions($reportType, $parameters) {
    $userRole = $_SESSION['role'];
    $userId = $_SESSION['user_id'];
    
    switch ($userRole) {
        case 'hr_admin':
            // HR admins can generate any report
            return true;
            
        case 'manager':
            // Managers can only generate reports for their team members
            if (isset($parameters['employee_id'])) {
                $employee = fetchOne("SELECT manager_id FROM employees WHERE employee_id = ?", [$parameters['employee_id']]);
                if (!$employee || $employee['manager_id'] != $_SESSION['employee_id']) {
                    throw new Exception('You can only generate reports for your team members');
                }
            }
            
            if (isset($parameters['manager_id']) && $parameters['manager_id'] != $_SESSION['employee_id']) {
                throw new Exception('You can only generate reports for yourself as a manager');
            }
            
            return true;
            
        case 'employee':
            // Employees can only generate reports for themselves
            if (isset($parameters['employee_id']) && $parameters['employee_id'] != $_SESSION['employee_id']) {
                throw new Exception('You can only generate reports for yourself');
            }
            
            // Employees cannot generate manager overview reports
            if ($reportType === 'manager_overview') {
                throw new Exception('Insufficient permissions for this report type');
            }
            
            return true;
            
        default:
            throw new Exception('Invalid user role');
    }
}

/**
 * Get report statistics for dashboard
 */
function getReportStatistics() {
    $stats = [];
    
    // Total reports generated
    $stats['total_reports'] = fetchOne("SELECT COUNT(*) as count FROM report_history")['count'];
    
    // Reports by type
    $stats['by_type'] = fetchAll("SELECT report_type, COUNT(*) as count FROM report_history GROUP BY report_type ORDER BY count DESC");
    
    // Recent activity
    $stats['recent_activity'] = fetchAll("SELECT report_name, report_type, generated_at FROM report_history ORDER BY generated_at DESC LIMIT 10");
    
    // Scheduled reports
    $stats['scheduled_count'] = fetchOne("SELECT COUNT(*) as count FROM scheduled_reports WHERE is_active = TRUE")['count'];
    
    return $stats;
}

// Handle statistics request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'statistics') {
    if ($_SESSION['role'] !== 'hr_admin') {
        throw new Exception('Insufficient permissions');
    }
    
    $stats = getReportStatistics();
    
    echo json_encode([
        'success' => true,
        'statistics' => $stats
    ]);
    exit;
}

/**
 * Validate report parameters
 */
function validateReportParameters($reportType, $parameters) {
    switch ($reportType) {
        case 'evidence_summary':
            // Optional parameters, no strict validation needed
            break;
            
        case 'performance_trends':
            if (empty($parameters['employee_id'])) {
                throw new Exception('Employee ID is required for performance trends report');
            }
            break;
            
        case 'manager_overview':
            if (empty($parameters['manager_id'])) {
                throw new Exception('Manager ID is required for manager overview report');
            }
            break;
            
        case 'custom':
            if (empty($parameters['title'])) {
                throw new Exception('Title is required for custom reports');
            }
            break;
            
        default:
            throw new Exception('Invalid report type');
    }
}

/**
 * Get available export formats
 */
function getExportFormats() {
    return [
        'pdf' => [
            'name' => 'PDF Document',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf'
        ],
        'excel' => [
            'name' => 'Excel Spreadsheet',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx'
        ],
        'csv' => [
            'name' => 'CSV File',
            'mime_type' => 'text/csv',
            'extension' => 'csv'
        ]
    ];
}

// Handle export formats request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'export_formats') {
    $formats = getExportFormats();
    
    echo json_encode([
        'success' => true,
        'formats' => $formats
    ]);
    exit;
}
?>