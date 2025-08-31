<?php
/**
 * public/api/upward-feedback/summary.php
 * Get aggregated feedback summary for manager evaluation (GET)
 *
 * Query params:
 *   manager_eval_id (required)
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": {
 *     "manager_eval_id": 12,
 *     "manager": { ... },
 *     "period_id": 5,
 *     "summary": { ... }
 *   },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 *
 * Only HR Admins and the evaluated manager may retrieve this summary.
 */
 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../classes/UpwardFeedbackManager.php';
require_once __DIR__ . '/../../../classes/Employee.php';

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

    if (!isset($_GET['manager_eval_id']) || !ctype_digit(strval($_GET['manager_eval_id']))) {
        json_response(false, null, 'Missing or invalid manager_eval_id.', 400);
    }
    $evalId = intval($_GET['manager_eval_id']);

    $ufm = new UpwardFeedbackManager();

    // Fetch evaluation to check permissions
    $evalRow = fetchOne("SELECT manager_eval_id, manager_employee_id, period_id FROM manager_evaluations WHERE manager_eval_id = ?", [$evalId]);
    if (!$evalRow) {
        json_response(false, null, 'Manager evaluation not found.', 404);
    }

    // Require authentication to view summary
    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    $current = getCurrentUser();
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($current['employee_id'] ?? null);

    // Authorization: HR Admin or the evaluated manager can access
    if (!isHRAdmin() && intval($currentEmployeeId) !== intval($evalRow['manager_employee_id'])) {
        json_response(false, null, 'Insufficient permissions to view this summary.', 403);
    }

    try {
        $summary = $ufm->aggregateFeedback($evalId);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to aggregate feedback: ' . $e->getMessage(), 500);
    }

    // Include basic manager info
    $emp = new Employee();
    $manager = $emp->getEmployeeById(intval($evalRow['manager_employee_id']));

    $data = [
        'manager_eval_id' => $evalId,
        'manager' => $manager ? $manager : ['employee_id' => intval($evalRow['manager_employee_id'])],
        'period_id' => intval($evalRow['period_id']),
        'summary' => $summary
    ];

    json_response(true, $data, 'Aggregated feedback retrieved.', 200);

} catch (Throwable $e) {
    error_log("Upward feedback summary endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}