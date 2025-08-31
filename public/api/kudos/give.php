<?php
/**
 * public/api/kudos/give.php
 * Give KUDOS recognition (POST)
 *
 * Uses KudosManager::giveKudos and the authentication helpers in includes/auth.php
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, null, 'Method Not Allowed. Use POST.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 'Unauthorized', 401);
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

    // Use current user as giver by default
    $current = getCurrentUser();
    if (!$current || !isset($current['employee_id'])) {
        json_response(false, null, 'Unable to determine current user.', 401);
    }
    $giverId = intval($current['employee_id']);

    // Allow admins to specify giver_id explicitly
    if (isset($input['giver_id']) && is_numeric($input['giver_id'])) {
        if (isHRAdmin()) {
            $giverId = intval($input['giver_id']);
        } else {
            // Only HR Admin can act on behalf of others
            json_response(false, null, 'Insufficient permission to set giver_id.', 403);
        }
    }

    // Validate receiver_id
    if (!isset($input['receiver_id']) || !ctype_digit(strval($input['receiver_id']))) {
        json_response(false, null, 'Missing or invalid receiver_id.', 400);
    }
    $receiverId = intval($input['receiver_id']);

    // Prevent giving kudos to oneself
    if ($giverId === $receiverId) {
        json_response(false, null, 'Cannot give kudos to yourself.', 400);
    }

    // Check access to receiver
    if (!canAccessEmployee($receiverId) && !isHRAdmin()) {
        // Managers can give to their team; employees can give to anyone? enforce at least authentication
        // Here we require that the giver can access the receiver unless HR admin
        json_response(false, null, 'You are not allowed to give kudos to this employee.', 403);
    }

    // Message or template required
    $message = isset($input['message']) ? sanitizeInput($input['message']) : '';
    $templateId = isset($input['template_id']) && ctype_digit(strval($input['template_id'])) ? intval($input['template_id']) : null;
    if (empty($message) && empty($templateId)) {
        json_response(false, null, 'Either message or template_id is required.', 400);
    }

    // Optional fields
    $categoryId = isset($input['category_id']) && ctype_digit(strval($input['category_id'])) ? intval($input['category_id']) : null;
    $points = isset($input['points_awarded']) && is_numeric($input['points_awarded']) ? intval($input['points_awarded']) : 0;
    $isPublic = isset($input['is_public']) ? (bool)$input['is_public'] : true;
    $tags = [];
    if (isset($input['tags'])) {
        if (is_array($input['tags'])) $tags = array_map('sanitizeInput', $input['tags']);
        else $tags = array_map('trim', explode(',', $input['tags']));
    }

    $payload = [
        'message' => $message,
        'category_id' => $categoryId,
        'template_id' => $templateId,
        'points_awarded' => $points,
        'is_public' => $isPublic,
        'tags' => $tags,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $km = new KudosManager();

    // Call backend giveKudos
    try {
        $kudosId = $km->giveKudos($giverId, $receiverId, $payload);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to give kudos: ' . $e->getMessage(), 500);
    }

    if (!$kudosId) {
        json_response(false, null, 'Could not create kudos recognition.', 500);
    }

    // Attempt to fetch created record with feed helper
    $feed = $km->getKudosFeed(['recipient_id' => $receiverId, 'limit' => 1]);
    $createdRecord = !empty($feed) ? $feed[0] : ['kudos_id' => $kudosId];

    json_response(true, $createdRecord, 'Kudos given successfully.', 201);

} catch (Throwable $e) {
    error_log("Kudos give endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}