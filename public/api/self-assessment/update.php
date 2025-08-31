<?php
/**
 * Self-Assessment - Update Endpoint
 *
 * PUT /public/api/self-assessment/update.php
 *
 * Updates an existing self-assessment using the SelfAssessmentManager.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../../config/config.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    exit;
}

// Standard headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Basic rate-limit headers (placeholders)
header('X-RateLimit-Limit: 60');
header('X-RateLimit-Remaining: 59');
header('X-RateLimit-Reset: ' . (time() + 60));

requireAuth();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Accept PUT or POST with _method=PUT fallback
    $override = $_POST['_method'] ?? $_GET['_method'] ?? null;
    if ($method !== 'PUT' && strtoupper($override) !== 'PUT') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method Not Allowed',
            'timestamp' => date('c')
        ]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload');
    }

    // Required
    if (empty($input['assessment_id'])) {
        http_response_code(400);
        throw new Exception('Field assessment_id is required');
    }

    $assessmentId = (int)$input['assessment_id'];
    $answers = $input['answers'] ?? null;
    $notes = isset($input['notes']) ? trim(filter_var($input['notes'], FILTER_SANITIZE_STRING)) : null;
    $metadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : null;

    // Current user info
    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $manager = new SelfAssessmentManager();

    // Fetch existing assessment to check ownership/status
    if (!method_exists($manager, 'getAssessmentById')) {
        throw new Exception('Self-assessment retrieval method not available');
    }

    $existing = $manager->getAssessmentById($assessmentId);
    if (!$existing) {
        http_response_code(404);
        throw new Exception('Assessment not found');
    }

    // Authorization:
    // - Employees can update their own assessments only and only if not yet submitted
    // - Managers and HR admins can update any (depending on permissions)
    $ownerEmployeeId = (int)($existing['employee_id'] ?? 0);
    $status = $existing['status'] ?? 'draft';

    if ($userRole === 'employee') {
        if ($ownerEmployeeId !== $currentEmployeeId) {
            http_response_code(403);
            throw new Exception('Employees can only update their own assessments');
        }
        if (in_array($status, ['submitted','locked','closed'])) {
            http_response_code(403);
            throw new Exception('Cannot modify an assessment that has already been submitted or locked');
        }
    } else {
        // Managers: allow only if they manage the employee or have HR permissions
        if ($userRole === 'manager' && function_exists('isManagerOf')) {
            if (!isManagerOf($currentEmployeeId, $ownerEmployeeId) && ($_SESSION['role'] ?? '') !== 'hr_admin') {
                http_response_code(403);
                throw new Exception('Managers can only update assessments for their direct reports');
            }
        }
    }

    // Prepare update payload
    $updatePayload = [
        'assessment_id' => $assessmentId,
        'answers' => $answers,
        'notes' => $notes,
        'metadata' => $metadata,
        'updated_by' => $userId
    ];

    // Validate if method available
    if (method_exists($manager, 'validateAssessmentData')) {
        $validation = $manager->validateAssessmentData($ownerEmployeeId, $existing['period_id'] ?? null, $answers ?? [], $metadata ?? []);
        if ($validation !== true) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $validation,
                'timestamp' => date('c')
            ]);
            exit;
        }
    }

    // Perform update
    if (!method_exists($manager, 'updateAssessment')) {
        throw new Exception('Self-assessment update method not available');
    }

    $success = $manager->updateAssessment($updatePayload);

    // Log activity
    if (function_exists('logActivity')) {
        logActivity($userId, 'api_self_assessment_update', 'self_assessment', $assessmentId, null, [
            'employee_id' => $ownerEmployeeId,
            'status_before' => $status
        ]);
    } else {
        error_log("Self-assessment updated by user {$userId}: assessment_id={$assessmentId}");
    }

    echo json_encode([
        'success' => true,
        'data' => ['assessment_id' => $assessmentId],
        'message' => 'Self-assessment updated',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Self-assessment update API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    if (!headers_sent()) {
        // Use 400 for generic errors, but preserve 403/404 if already set
        $code = http_response_code() ?: 400;
        http_response_code($code >= 400 ? $code : 400);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}