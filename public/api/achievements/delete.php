<?php
/*
 * public/api/achievements/delete.php
 * Deletes an achievement journal entry by id
 *
 * Expected:
 *  - POST /public/api/achievements/delete.php
 *    Body (application/x-www-form-urlencoded or JSON): { "id": 123 }
 *
 * Response:
 *  - JSON: { success: true } or { success: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Accept POST (or DELETE tunneled via POST)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
        exit;
    }

    // Read input (support JSON body)
    $input = $_POST;
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    $id = isset($input['id']) ? $input['id'] : null;

    if (empty($id) || !ctype_digit(strval($id))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid "id" parameter.']);
        exit;
    }

    // Load the AchievementJournal class
    require_once __DIR__ . '/../../../classes/AchievementJournal.php';

    $aj = new AchievementJournal();
    $deleted = $aj->delete(intval($id));

    if (!$deleted) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Achievement not found or could not be deleted.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    exit;
}
?>