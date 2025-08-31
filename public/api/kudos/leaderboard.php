<?php
/**
 * public/api/kudos/leaderboard.php
 * Get KUDOS leaderboard (GET)
 *
 * Query parameters:
 *  - type (monthly|total) default: monthly
 *  - limit (int) default: 10, max: 50
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

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    $type = isset($_GET['type']) && in_array($_GET['type'], ['monthly','total']) ? $_GET['type'] : 'monthly';
    $limit = isset($_GET['limit']) && ctype_digit(strval($_GET['limit'])) ? intval($_GET['limit']) : 10;
    if ($limit <= 0) $limit = 1;
    if ($limit > 50) $limit = 50;

    $km = new KudosManager();
    $board = $km->getLeaderboard($type, $limit);

    json_response(true, $board, 'Leaderboard retrieved successfully.', 200);

} catch (Throwable $e) {
    error_log("Kudos leaderboard endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}