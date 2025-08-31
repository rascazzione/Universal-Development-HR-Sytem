<?php
/*
 * public/api/kudos/list.php
 * Lists KUDOS entries with pagination and optional filters
 *
 * GET parameters:
 *  - page (default 1)
 *  - page_size (default 20, max 200)
 *  - tag (optional)
 *  - receiver_id (optional)
 *  - giver_id (optional)
 *
 * Response:
 *  - { success: true, data: [...], meta: { total, page, page_size } }
 */

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use GET.']);
        exit;
    }

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $page_size = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
    if ($page_size <= 0) $page_size = 20;
    if ($page_size > 200) $page_size = 200;
    $offset = ($page - 1) * $page_size;

    $filters = [];
    if (isset($_GET['tag']) && $_GET['tag'] !== '') $filters['tag'] = trim($_GET['tag']);
    if (isset($_GET['receiver_id']) && ctype_digit(strval($_GET['receiver_id']))) $filters['receiver_id'] = intval($_GET['receiver_id']);
    if (isset($_GET['giver_id']) && ctype_digit(strval($_GET['giver_id']))) $filters['giver_id'] = intval($_GET['giver_id']);

    require_once __DIR__ . '/../../../classes/KudosManager.php';
    $km = new KudosManager();

    $results = $km->list($page_size, $offset, $filters);
    $total = $km->count($filters);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'meta' => [
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    exit;
}
?>