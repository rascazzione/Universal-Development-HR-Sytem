<?php
/*
 * public/api/kudos/create.php
 * Creates a KUDOS recognition entry
 *
 * Expected:
 *  - POST /public/api/kudos/create.php
 *    Body (application/json) example:
 *    {
 *      "giver_id": 12,
 *      "receiver_id": 34,
 *      "message": "Great teamwork on the project!",
 *      "tags": ["teamwork","project"],
 *      "anonymous": false
 *    }
 *
 * Response:
 *  - JSON: { success: true, data: { id: 123, ... } } or { success: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
        exit;
    }

    // Read body (support JSON and form-encoded)
    $input = $_POST;
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    // Basic validation
    $required = ['giver_id', 'receiver_id', 'message'];
    foreach ($required as $f) {
        if (!isset($input[$f]) || $input[$f] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: {$f}"]);
            exit;
        }
    }

    if (!ctype_digit(strval($input['giver_id'])) || !ctype_digit(strval($input['receiver_id']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'giver_id and receiver_id must be integers.']);
        exit;
    }

    // Normalize fields
    $payload = [
        'giver_id' => intval($input['giver_id']),
        'receiver_id' => intval($input['receiver_id']),
        'message' => trim($input['message']),
        'tags' => isset($input['tags']) && is_array($input['tags']) ? array_values($input['tags']) : (isset($input['tags']) ? explode(',', $input['tags']) : []),
        'anonymous' => isset($input['anonymous']) ? boolval($input['anonymous']) : false,
        'created_at' => date('Y-m-d H:i:s')
    ];

    require_once __DIR__ . '/../../../classes/KudosManager.php';
    $km = new KudosManager();

    // Expect create to return inserted id or the created record
    $created = $km->create($payload);

    if (!$created) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create kudos.']);
        exit;
    }

    // If create returned id, attempt to fetch full record
    if (is_int($created) || ctype_digit(strval($created))) {
        $record = $km->get(intval($created));
    } else {
        $record = $created;
    }

    echo json_encode(['success' => true, 'data' => $record]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    exit;
}
?>