<?php
/**
 * Self-Assessment - Submit Endpoint
 *
 * POST /public/api/self-assessment/submit.php
 *
 * Submits a self-assessment for review.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';
require_once __DIR__ . '/../../config/config.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method Not Allowed',
            'timestamp' => date('c')
        ]);
        exit;
    }

    // Optional CSRF protection if session token exists
    if (isset($_SESSION['csrf_token'])) {
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$csrf || $csrf !== $_SESSION['csrf_token']) {
            http_response_code(401);
            throw new Exception('Invalid CSRF token');
        }
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload');
    }

    if (empty($input['assessment_id'])) {
        http_response_code(400);
        throw new Exception('Field assessment_id is required');
    }

    $assessmentId = (int)$input['assessment_id'];

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $manager = new SelfAssessmentManager();
    $notificationManager = new NotificationManager();

    if (!method_exists($manager, 'getAssessmentById')) {
        throw new Exception('Assessment retrieval not available');
    }

    $existing = $manager->getAssessmentById($assessmentId);
    if (!$existing) {
        http_response_code(404);
        throw new Exception('Assessment not found');
    }

    $ownerEmployeeId = (int)($existing['employee_id'] ?? 0);
    $status = $existing['status'] ?? 'draft';

    // Authorization checks
    if ($userRole === 'employee' && $ownerEmployeeId !== $currentEmployeeId) {
        http_response_code(403);
        throw new Exception('Employees can only submit their own assessments');
    }

    if (in_array($status, ['submitted', 'locked', 'closed'])) {
        http_response_code(403);
        throw new Exception('Assessment is already submitted or locked');
    }

    if (!method_exists($manager, 'submitAssessment')) {
        throw new Exception('Submit method not available');
    }

    // Submit the assessment (manager handles DB and business rules)
    $result = $manager->submitAssessment($assessmentId, $userId);

    // Trigger notification to manager if possible
    $managerId = $existing['manager_id'] ?? null;
    if ($managerId && method_exists($notificationManager, 'createNotification')) {
        try {
            $notificationManager->createNotification([
                'user_id' => $managerId,
                'type' => 'assessment_submitted',
                'title' => 'Self-assessment submitted',
                'message' => "Employee #{$ownerEmployeeId} submitted their self-assessment for review.",
                'priority' => 'high'
            ]);
        } catch (Exception $notifyEx) {
            // Log but do not fail the request
            error_log("Failed to notify manager {$managerId} for assessment {$assessmentId}: " . $notifyEx->getMessage());
        }
    }

    // Log activity
    if (function_exists('logActivity')) {
        logActivity($userId, 'api_self_assessment_submit', 'self_assessment', $assessmentId, null, [
            'employee_id' => $ownerEmployeeId,
            'previous_status' => $status
        ]);
    } else {
        error_log("Self-assessment submitted by user {$userId}: assessment_id={$assessmentId}");
    }

    echo json_encode([
        'success' => true,
        'data' => ['assessment_id' => $assessmentId, 'result' => $result],
        'message' => 'Self-assessment submitted',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Self-assessment submit API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
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