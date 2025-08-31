<?php
/**
 * public/api/kudos/react.php
 * React to a KUDOS (PUT)
 *
 * Standard JSON response:
 * {
 *   "success": true|false,
 *   "data": { ... },
 *   "message": "Success/Error message",
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
    // Accept PUT or POST with _method=PUT
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
        $method = 'PUT';
    }

    if ($method !== 'PUT') {
        json_response(false, null, 'Method Not Allowed. Use PUT.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    // Parse input (raw body)
    $raw = file_get_contents('php://input');
    $input = [];
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = $decoded;
        } else {
            // Fallback to parse as form-encoded
            parse_str($raw, $input);
        }
    } else {
        // Fall back to $_POST if present (when using POST with override)
        $input = $_POST;
    }

    // Validate kudos_id
    if (!isset($input['kudos_id']) || !ctype_digit(strval($input['kudos_id']))) {
        json_response(false, null, 'Missing or invalid kudos_id.', 400);
    }
    $kudosId = intval($input['kudos_id']);

    // Validate reaction_type
    $reactionType = isset($input['reaction_type']) ? trim($input['reaction_type']) : '';
    if ($reactionType === '') {
        json_response(false, null, 'Missing reaction_type.', 400);
    }

    // Current user
    $current = getCurrentUser();
    if (!$current || !isset($current['employee_id'])) {
        json_response(false, null, 'Unable to determine current user.', 401);
    }
    $employeeId = intval($current['employee_id']);

    $km = new KudosManager();

    try {
        $result = $km->reactToKudos($kudosId, $employeeId, $reactionType);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to react to kudos: ' . $e->getMessage(), 500);
    }

    if ($result === false) {
        json_response(false, null, 'No change made to reaction.', 200);
    }

    json_response(true, ['kudos_id' => $kudosId, 'reaction_type' => $reactionType], 'Reaction processed successfully.', 200);

} catch (Throwable $e) {
    error_log("Kudos react endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}