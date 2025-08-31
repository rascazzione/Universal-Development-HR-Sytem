<?php
/**
 * public/api/kudos/feed.php
 * Get KUDOS feed with filters (GET)
 *
 * Accepts query parameters:
 *  - recipient_id
 *  - sender_id
 *  - category_id
 *  - start_date (YYYY-MM-DD)
 *  - end_date (YYYY-MM-DD)
 *  - is_public (0|1)
 *  - limit (default 50, max 200)
 *
 * Standard JSON response:
 * {
 *   "success": true|false,
 *   "data": [ ... ],
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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(false, null, 'Method Not Allowed. Use GET.', 405);
    }

    $filters = [];

    if (isset($_GET['recipient_id']) && ctype_digit(strval($_GET['recipient_id']))) $filters['recipient_id'] = intval($_GET['recipient_id']);
    if (isset($_GET['sender_id']) && ctype_digit(strval($_GET['sender_id']))) $filters['sender_id'] = intval($_GET['sender_id']);
    if (isset($_GET['category_id']) && ctype_digit(strval($_GET['category_id']))) $filters['category_id'] = intval($_GET['category_id']);
    if (isset($_GET['start_date']) && $_GET['start_date'] !== '') $filters['start_date'] = sanitizeInput($_GET['start_date']);
    if (isset($_GET['end_date']) && $_GET['end_date'] !== '') $filters['end_date'] = sanitizeInput($_GET['end_date']);
    if (isset($_GET['is_public'])) $filters['is_public'] = ($_GET['is_public'] === '1' || $_GET['is_public'] === 'true' || $_GET['is_public'] === 'on') ? true : false;

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    if ($limit <= 0) $limit = 1;
    if ($limit > 200) $limit = 200;
    $filters['limit'] = $limit;

    // Authorization: if recipient_id is set, ensure current user can view it
    if (isset($filters['recipient_id'])) {
        if (!isAuthenticated()) {
            json_response(false, null, 'Authentication required.', 401);
        }
        if (!canAccessEmployee($filters['recipient_id']) && !isHRAdmin()) {
            json_response(false, null, 'You are not allowed to view this feed.', 403);
        }
    }

    $km = new KudosManager();
    $feed = $km->getKudosFeed($filters);

    json_response(true, $feed, 'Feed retrieved successfully.', 200);

} catch (Throwable $e) {
    error_log("Kudos feed endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}