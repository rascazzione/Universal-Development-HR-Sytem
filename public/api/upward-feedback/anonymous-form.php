<?php
/**
 * public/api/upward-feedback/anonymous-form.php
 * Get anonymous feedback form by token (GET)
 *
 * Query: ?token=PLAINTOKEN
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": {
 *     "manager_id": 12,
 *     "manager_name": "Jane Doe",
 *     "period_id": 5,
 *     "expires_at": "ISO datetime",
 *     "questions": [ ... ] // optional, could be pulled from a template or settings
 *   },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 *
 * Note: This endpoint must not expose any mapping between token and intended recipient.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../classes/UpwardFeedbackManager.php';
require_once __DIR__ . '/../../../classes/Employee.php';
require_once __DIR__ . '/../../../classes/EvaluationPeriod.php';

function json_response($success, $data = null, $message = '', $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(false, null, 'Method Not Allowed. Use GET.', 405);
    }

    if (!isset($_GET['token']) || empty($_GET['token'])) {
        json_response(false, null, 'Missing token parameter.', 400);
    }
    $plainToken = $_GET['token'];

    $hashedToken = hash('sha512', $plainToken);
    $tracking = fetchOne("SELECT tracking_id, response_id, created_at, expires_at FROM anonymous_response_tracking WHERE sent_token = ?", [$hashedToken]);

    if (!$tracking) {
        json_response(false, null, 'Invalid token.', 404);
    }

    if (!empty($tracking['expires_at']) && strtotime($tracking['expires_at']) < time()) {
        json_response(false, null, 'Token expired.', 410);
    }

    // Fetch response row to obtain manager and period
    $response = fetchOne("SELECT manager_employee_id, period_id FROM upward_feedback_responses WHERE response_id = ?", [$tracking['response_id']]);
    if (!$response) {
        json_response(false, null, 'Associated response not found.', 404);
    }

    $managerId = intval($response['manager_employee_id']);
    $periodId = intval($response['period_id']);

    $emp = new Employee();
    $manager = $emp->getEmployeeById($managerId);

    $period = fetchOne("SELECT period_id, name, start_date, end_date FROM evaluation_periods WHERE period_id = ?", [$periodId]);

    // Questions could be pulled from a settings table or template; provide a safe default schema
    $questions = [
        ['id' => 'q1', 'text' => 'Rate manager on communication (1-5)', 'type' => 'rating'],
        ['id' => 'q2', 'text' => 'Rate manager on support and growth (1-5)', 'type' => 'rating'],
        ['id' => 'q3', 'text' => 'Provide constructive feedback (optional)', 'type' => 'text']
    ];

    $data = [
        'manager_id' => $managerId,
        'manager_name' => $manager ? getFullName($manager) : 'Manager',
        'period_id' => $periodId,
        'period_name' => $period['name'] ?? null,
        'period_start' => $period['start_date'] ?? null,
        'period_end' => $period['end_date'] ?? null,
        'expires_at' => $tracking['expires_at'],
        'questions' => $questions
    ];

    json_response(true, $data, 'Token valid. Form retrieved.', 200);

} catch (Throwable $e) {
    error_log("Anonymous form endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}