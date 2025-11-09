<?php
/**
 * Job Template API
 * Returns job template details for an employee's feedback form
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';
require_once __DIR__ . '/../../classes/Employee.php';

// JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Require authentication
    requireAuth();

    // Detect if this is self-feedback (autofeedback)
    $currentUserEmployeeId = $_SESSION['employee_id'] ?? null;
    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
    $isSelfFeedback = ($employeeId == $currentUserEmployeeId);

    // Allow if it's autofeedback or if user is manager/hr_admin
    if (!$isSelfFeedback && !in_array($_SESSION['user_role'], ['manager', 'hr_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    // Validate employee ID (already defined above)
    if ($employeeId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid employee_id']);
        exit;
    }

    // Validate dimension parameter
    $dimension = $_GET['dimension'] ?? '';
    if (!in_array($dimension, ['responsibilities', 'kpis', 'competencies', 'values', 'all'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid dimension parameter']);
        exit;
    }

    // Load employee and job template
    $employeeClass = new Employee();
    $jobTemplateClass = new JobTemplate();
    
    $employee = $employeeClass->getEmployeeById($employeeId);
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    // Check if current user can give feedback to this employee
    if (!$isSelfFeedback) {
        // If NOT self-feedback, validate that it's a manager of the employee
        if ($_SESSION['user_role'] === 'manager' && $employee['manager_id'] != $currentUserEmployeeId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solo puedes ver templates de tus subordinados']);
            exit;
        }
    }

    // Get job template data
    $jobTemplateId = $employee['job_template_id'] ?? null;
    $templateData = [];
    
            if ($jobTemplateId) {
                $template = $jobTemplateClass->getJobTemplateById($jobTemplateId);
                if ($template) {
                    $templateData['template'] = $template;
                    
                    if ($dimension === 'all' || $dimension === 'responsibilities') {
                        $templateData['responsibilities'] = $jobTemplateClass->getTemplateResponsibilities($jobTemplateId);
                    }
                    
                    if ($dimension === 'all' || $dimension === 'kpis') {
                        $templateData['kpis'] = $jobTemplateClass->getTemplateKPIs($jobTemplateId);
                    }
                    
                    if ($dimension === 'all' || $dimension === 'competencies') {
                        $skills = $jobTemplateClass->getTemplateSkills($jobTemplateId);
                        $templateData['technical_skills'] = $skills['technical'];
                        $templateData['soft_skills'] = $skills['soft_skill'];
                        $templateData['competencies'] = $skills['all'];
                    }
                    
                    if ($dimension === 'all' || $dimension === 'values') {
                        $templateData['values'] = $jobTemplateClass->getTemplateValues($jobTemplateId);
                    }
                }
            }

    // Success
    echo json_encode([
        'success' => true,
        'employee' => [
            'id' => $employee['employee_id'],
            'name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'position' => $employee['position'],
            'department' => $employee['department']
        ],
        'job_template' => $templateData
    ]);
} catch (Throwable $e) {
    error_log('API job-template.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
