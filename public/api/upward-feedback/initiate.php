<?php
/**
 * public/api/upward-feedback/initiate.php
 * Initiate manager evaluation (POST)
 *
 * Expected JSON body:
 * {
 *   "manager_id": 123,        // optional - defaults to current user's employee_id for managers
 *   "period_id": 5,           // required
 *   "generate_tokens": true   // optional - only HR Admin can generate tokens
 * }
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": { "manager_eval_id": 12, "tokens": [...] },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../classes/UpwardFeedbackManager.php';

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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, null, 'Method Not Allowed. Use POST.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    // Read input (support JSON)
    $input = $_POST;
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    // Validate period_id
    if (!isset($input['period_id']) || !ctype_digit(strval($input['period_id']))) {
        json_response(false, null, 'Missing or invalid period_id.', 400);
    }
    $periodId = intval($input['period_id']);

    $current = getCurrentUser();
    if (!$current || !isset($current['employee_id'])) {
        json_response(false, null, 'Unable to determine current user.', 401);
    }

    // Determine manager_id
    if (isset($input['manager_id']) && ctype_digit(strval($input['manager_id']))) {
        $managerId = intval($input['manager_id']);
        // Only HR Admin can initiate for other managers
        if ($managerId !== intval($current['employee_id']) && !isHRAdmin()) {
            json_response(false, null, 'Insufficient permission to initiate evaluation for another manager.', 403);
        }
    } else {
        // Default to current user (manager initiating for self)
        $managerId = intval($current['employee_id']);
        // Ensure the current user is a manager or hr_admin
        if (!isManager() && !isHRAdmin()) {
            json_response(false, null, 'Only managers or HR admins can initiate manager evaluations.', 403);
        }
    }

    $ufm = new UpwardFeedbackManager();

    try {
        $evalId = $ufm->initiateManagerEvaluation($managerId, $periodId);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to initiate manager evaluation: ' . $e->getMessage(), 500);
    }

    $responseData = ['manager_eval_id' => $evalId];

    // Optionally generate tokens (HR only)
    $generateTokens = isset($input['generate_tokens']) && ($input['generate_tokens'] === true || $input['generate_tokens'] === 'true' || $input['generate_tokens'] === '1');
    if ($generateTokens) {
        if (!isHRAdmin()) {
            json_response(false, null, 'Only HR Admin can generate anonymous tokens.', 403);
        }
        try {
            $tokens = $ufm->generateAnonymousTokens($evalId);
            $responseData['tokens'] = $tokens;
        } catch (Exception $e) {
            // Return evaluation id but include token generation error
            $responseData['tokens'] = [];
            $responseData['token_error'] = $e->getMessage();
        }
    }

    json_response(true, $responseData, 'Manager evaluation initiated successfully.', 201);

} catch (Throwable $e) {
    error_log("Upward feedback initiate endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}