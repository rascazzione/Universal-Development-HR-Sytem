<?php
/**
 * public/api/okr/update.php
 * Update existing OKR (PUT)
 *
 * PUT JSON:
 * {
 *   "goal_id": 123,               // required
 *   "title": "...",               // optional
 *   "description": "...",         // optional
 *   "target_date": "YYYY-MM-DD",  // optional
 *   "confidence": "low|medium|high",
 *   "cycle": "monthly|quarterly|annual",
 *   "parent_goal_id": 12,
 *   "key_results": [ ... ]
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
 * - Owner employee, their manager, or HR Admin can update.
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
    // Allow CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: PUT, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        json_response(false, null, 'Method Not Allowed. Use PUT.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    // Read raw JSON body
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        json_response(false, null, 'Invalid JSON payload.', 400);
    }

    if (empty($input['goal_id']) || !ctype_digit(strval($input['goal_id']))) {
        json_response(false, null, 'Missing or invalid goal_id.', 400);
    }
    $goalId = intval($input['goal_id']);

    // Fetch goal
    $goal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$goalId]);
    if (!$goal) {
        json_response(false, null, 'Goal not found.', 404);
    }

    $current = getCurrentUser();
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($current['employee_id'] ?? null);
    $userRole = $_SESSION['user_role'] ?? ($current['role'] ?? 'employee');

    // Authorization: HR Admin or owner or manager of owner can edit
    $ownerEmployeeId = intval($goal['employee_id']);
    if (!isHRAdmin()) {
        if ($userRole === 'employee' && intval($currentEmployeeId) !== $ownerEmployeeId) {
            json_response(false, null, 'Employees can only update their own OKRs.', 403);
        }
        if ($userRole === 'manager' && intval($currentEmployeeId) !== $ownerEmployeeId) {
            if (!canAccessEmployee($ownerEmployeeId)) {
                json_response(false, null, 'Managers can only update OKRs for their direct reports.', 403);
            }
        }
    }

    // Build update set
    $fields = [];
    $params = [];
    $allowed = ['title','description','target_date','confidence','cycle','parent_goal_id','key_results'];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            if ($f === 'key_results' && is_array($input[$f])) {
                $fields[] = "okr_key_results = ?";
                $params[] = json_encode($input[$f]);
            } elseif ($f === 'parent_goal_id') {
                $fields[] = "parent_goal_id = ?";
                $params[] = ctype_digit(strval($input[$f])) ? intval($input[$f]) : null;
            } else {
                // map to performance_goals columns
                switch ($f) {
                    case 'title': $fields[] = "title = ?"; $params[] = $input[$f]; break;
                    case 'description': $fields[] = "description = ?"; $params[] = $input[$f]; break;
                    case 'target_date': $fields[] = "target_date = ?"; $params[] = $input[$f]; break;
                    case 'confidence': $fields[] = "okr_confidence = ?"; $params[] = $input[$f]; break;
                    case 'cycle': $fields[] = "okr_cycle = ?"; $params[] = $input[$f]; break;
                }
            }
        }
    }

    if (empty($fields)) {
        json_response(false, null, 'No updatable fields provided.', 400);
    }

    // Append updated_at
    $fields[] = "updated_at = NOW()";

    $sql = "UPDATE performance_goals SET " . implode(", ", $fields) . " WHERE goal_id = ?";
    $params[] = $goalId;

    $affected = updateRecord($sql, $params);

    if ($affected > 0) {
        // Log
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'] ?? null, 'okr_updated', 'performance_goals', $goalId, $goal, $input);
        }
        json_response(true, ['updated' => true], 'OKR updated successfully.', 200);
    } else {
        json_response(false, null, 'No changes applied.', 200);
    }

} catch (Throwable $e) {
    error_log("OKR update endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}