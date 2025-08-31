<?php
/**
 * public/api/okr/progress.php
 * Update OKR progress (POST)
 *
 * POST JSON:
 * {
 *   "goal_id": 123,          // required
 *   "progress": 50.5,        // required - 0..100
 *   "note": "Optional note"
 * }
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": { "updated": true },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 *
 * Authorization:
 * - Owner employee, their manager, or HR Admin can update progress.
 */
 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/OKRManager.php';

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
    // Support CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, null, 'Method Not Allowed. Use POST.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        json_response(false, null, 'Invalid JSON payload.', 400);
    }

    if (empty($input['goal_id']) || !ctype_digit(strval($input['goal_id']))) {
        json_response(false, null, 'Missing or invalid goal_id.', 400);
    }
    $goalId = intval($input['goal_id']);

    if (!isset($input['progress'])) {
        json_response(false, null, "Missing 'progress' field.", 400);
    }
    $progress = floatval($input['progress']);
    $note = isset($input['note']) ? trim($input['note']) : null;

    // Fetch goal to check permissions
    $goal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$goalId]);
    if (!$goal) {
        json_response(false, null, 'Goal not found.', 404);
    }

    $current = getCurrentUser();
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($current['employee_id'] ?? null);
    $userRole = $_SESSION['user_role'] ?? ($current['role'] ?? 'employee');

    // Authorization: owner, manager, or HR admin
    $ownerEmployeeId = intval($goal['employee_id']);
    if (!isHRAdmin()) {
        if ($userRole === 'employee' && intval($currentEmployeeId) !== $ownerEmployeeId) {
            json_response(false, null, 'Employees can only update progress for their own OKRs.', 403);
        }
        if ($userRole === 'manager' && intval($currentEmployeeId) !== $ownerEmployeeId) {
            if (!canAccessEmployee($ownerEmployeeId)) {
                json_response(false, null, 'Managers can only update progress for their direct reports.', 403);
            }
        }
    }

    // Validate progress range
    if ($progress < 0 || $progress > 100) {
        json_response(false, null, 'Progress must be between 0 and 100.', 400);
    }

    $okr = new OKRManager();

    try {
        $ok = $okr->updateProgress($goalId, ['progress' => $progress, 'note' => $note]);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to update progress: ' . $e->getMessage(), 500);
    }

    if ($ok) {
        json_response(true, ['updated' => true], 'Progress updated.', 200);
    } else {
        json_response(false, null, 'No update recorded.', 200);
    }

} catch (Throwable $e) {
    error_log("OKR progress endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}