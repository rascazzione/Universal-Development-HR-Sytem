<?php
/*
 * public/api/achievements/get.php
 * Returns a single achievement journal entry by id
 *
 * Expected:
 *  - GET /public/api/achievements/get.php?id=123
 *
 * Response:
 *  - JSON: { success: true, data: { ... } } or { success: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Basic method check
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use GET.']);
        exit;
    }

    // Allow id via query string
    $id = isset($_GET['id']) ? trim($_GET['id']) : null;

    if (empty($id) || !ctype_digit(strval($id))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid "id" parameter.']);
        exit;
    }

    // Load the AchievementJournal class
    // classes/ is expected at the project root; adjust relative path from this file.
    require_once __DIR__ . '/../../../classes/AchievementJournal.php';

    // Instantiate and fetch
    $aj = new AchievementJournal();
    $record = $aj->get(intval($id));

    if (!$record) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Achievement not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $record]);
    exit;

} catch (Throwable $e) {
    // Generic error handling
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    exit;
}
?>