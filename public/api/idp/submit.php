<?php
// public/api/idp/submit.php
// Mark an IDP item as submitted
// Follows the auth/response style used by other public/api endpoints.

require_once __DIR__ . '/../../inc/bootstrap.php';

header('Content-Type: application/json');

// Basic auth / session check
if (empty($current_user) || empty($current_user['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Accept id via POST JSON body or query string
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
    exit;
}

$table = 'idp_items';
$now = date('Y-m-d H:i:s');

try {
    // Fetch existing item
    $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not found']);
        exit;
    }

    $ownerId = isset($item['user_id']) ? (int)$item['user_id'] : null;
    $currentUserId = (int)$current_user['id'];
    $is_admin = !empty($current_user['is_admin']) || (!empty($current_user['role']) && $current_user['role'] === 'admin');

    // Permission check: owner, direct manager of owner, or admin
    $allowed = false;
    if ($is_admin || $currentUserId === $ownerId) {
        $allowed = true;
    } else {
        // Check owner's manager_id in users table
        $uStmt = $db->prepare("SELECT manager_id FROM users WHERE id = :uid LIMIT 1");
        $uStmt->bindValue(':uid', $ownerId, PDO::PARAM_INT);
        $uStmt->execute();
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        $manager_id = $uRow ? (isset($uRow['manager_id']) ? (int)$uRow['manager_id'] : null) : null;

        if ($manager_id !== null && $currentUserId === $manager_id) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    // If already submitted, return ok
    $current_status = isset($item['status']) ? strtolower($item['status']) : '';
    if ($current_status === 'submitted') {
        echo json_encode(['success' => true, 'message' => 'Already submitted', 'item' => $item]);
        exit;
    }

    // Update status to 'submitted' and updated_at
    $upd = $db->prepare("UPDATE {$table} SET status = :status, updated_at = :updated_at WHERE id = :id");
    $upd->bindValue(':status', 'submitted', PDO::PARAM_STR);
    $upd->bindValue(':updated_at', $now, PDO::PARAM_STR);
    $upd->bindValue(':id', $id, PDO::PARAM_INT);
    $upd->execute();

    // Return updated item
    $select = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $select->bindValue(':id', $id, PDO::PARAM_INT);
    $select->execute();
    $updated = $select->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'item' => $updated]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}