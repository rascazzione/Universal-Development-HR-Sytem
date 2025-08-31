<?php
/**
 * Self-Assessment - Create Endpoint
 *
 * POST /public/api/self-assessment/create.php
 *
 * Creates a new self-assessment using the SelfAssessmentManager (Phase 2).
 * Follows existing API patterns from public/api/dashboard-data.php and public/api/notifications.php
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../../config/config.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Standard headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Basic rate-limit headers (placeholders; replace with real limiter if available)
header('X-RateLimit-Limit: 60');
header('X-RateLimit-Remaining: 59');
header('X-RateLimit-Reset: ' . (time() + 60));

// Require authentication
requireAuth();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Current user information
    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    // Required fields
    $required = ['employee_id', 'period_id', 'answers'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Field '$field' is required",
                'timestamp' => date('c')
            ]);
            exit;
        }
    }

    $employeeId = (int)$input['employee_id'];
    $periodId = (int)$input['period_id'];
    $answers = $input['answers']; // Expect array of question=>answer

    if (!is_array($answers) || empty($answers)) {
        throw new Exception('Answers must be a non-empty array');
    }

    // Authorization:
    // - Employees can create assessments for themselves only
    // - Managers and HR admins can create for others
    if ($userRole === 'employee' && $employeeId !== $currentEmployeeId) {
        http_response_code(403);
        throw new Exception('Employees can only create self-assessments for themselves');
    }

    // Sanitize optional fields
    $notes = isset($input['notes']) ? trim(filter_var($input['notes'], FILTER_SANITIZE_STRING)) : null;
    $metadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : null;

    $manager = new SelfAssessmentManager();

    // Optional: Use manager validation hook if available
    if (method_exists($manager, 'validateAssessmentData')) {
        $validation = $manager->validateAssessmentData($employeeId, $periodId, $answers, $metadata ?? []);
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

    // Create assessment
    $payload = [
        'employee_id' => $employeeId,
        'period_id' => $periodId,
        'answers' => $answers,
        'notes' => $notes,
        'metadata' => $metadata,
        'created_by' => $userId
    ];

    $assessmentId = $manager->createAssessment($payload);

    // Log and notify
    if (function_exists('logActivity')) {
        logActivity($userId, 'api_self_assessment_create', 'self_assessment', $assessmentId, null, [
            'employee_id' => $employeeId,
            'period_id' => $periodId
        ]);
    } else {
        error_log("Self-assessment created by user {$userId}: assessment_id={$assessmentId}");
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => ['assessment_id' => $assessmentId],
        'message' => 'Self-assessment created',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Self-assessment create API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}