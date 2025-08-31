<?php
/**
 * public/api/kudos/points.php
 * Get employee KUDOS points and badges (GET)
 *
 * Query parameters:
 *  - employee_id (optional, defaults to current user)
 *  - period_id (optional) - to calculate points within a specific evaluation period
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "employee_id": 12,
 *     "total_points": 123,
 *     "monthly_points": 45,
 *     "period_points": 30, // if period_id provided
 *     "badges": [...]
 *   },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../classes/KudosManager.php';

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

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    $current = getCurrentUser();
    if (!$current || !isset($current['employee_id'])) {
        json_response(false, null, 'Unable to determine current user.', 401);
    }

    $employeeId = isset($_GET['employee_id']) && ctype_digit(strval($_GET['employee_id'])) ? intval($_GET['employee_id']) : intval($current['employee_id']);

    if ($employeeId !== intval($current['employee_id']) && !canAccessEmployee($employeeId) && !isHRAdmin()) {
        json_response(false, null, 'You are not allowed to view this employee points.', 403);
    }

    $km = new KudosManager();

    // Fetch points summary if table exists
    $pointsRow = fetchOne("SELECT total_points, monthly_points FROM employee_kudos_points WHERE employee_id = ?", [$employeeId]);

    $total = intval($pointsRow['total_points'] ?? 0);
    $monthly = intval($pointsRow['monthly_points'] ?? 0);

    $periodPoints = null;
    if (isset($_GET['period_id']) && ctype_digit(strval($_GET['period_id']))) {
        $periodId = intval($_GET['period_id']);
        $periodPoints = $km->calculatePoints($employeeId, $periodId);
    }

    $badges = $km->awardBadges($employeeId);

    $data = [
        'employee_id' => $employeeId,
        'total_points' => $total,
        'monthly_points' => $monthly,
        'period_points' => $periodPoints,
        'badges' => $badges
    ];

    json_response(true, $data, 'Points retrieved successfully.', 200);

} catch (Throwable $e) {
    error_log("Kudos points endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}