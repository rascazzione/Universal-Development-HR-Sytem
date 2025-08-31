<?php
// public/api/idp/update.php
// Update an existing IDP item
// Follows the auth/response style used by other public/api endpoints.

require_once __DIR__ . '/../../inc/bootstrap.php';

header('Content-Type: application/json');

// Basic auth / session check
if (empty($current_user) || empty($current_user['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Accept JSON body
$input = json_decode(file_get_contents('php://input'), true);

// Accept id from JSON or query string
$id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
    exit;
}

$table = 'idp_items';

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
        // Check owner manager
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

    // Collect updatable fields
    $fields = [];
    $params = [];
    if (isset($input['title'])) {
        $title = trim($input['title']);
        if ($title === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Validation error: title cannot be empty']);
            exit;
        }
        $fields[] = 'title = :title';
        $params[':title'] = $title;
    }
    if (array_key_exists('description', $input)) {
        $fields[] = 'description = :description';
        $params[':description'] = $input['description'];
    }
    if (isset($input['status'])) {
        $fields[] = 'status = :status';
        $params[':status'] = $input['status'];
    }
    if (array_key_exists('assignee_id', $input)) {
        if ($input['assignee_id'] === null || $input['assignee_id'] === '') {
            $fields[] = 'assignee_id = NULL';
        } else {
            $fields[] = 'assignee_id = :assignee_id';
            $params[':assignee_id'] = (int)$input['assignee_id'];
        }
    }
    if (isset($input['visibility'])) {
        $fields[] = 'visibility = :visibility';
        $params[':visibility'] = $input['visibility'];
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No updatable fields provided']);
        exit;
    }

    $fields[] = 'updated_at = :updated_at';
    $params[':updated_at'] = date('Y-m-d H:i:s');

    $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        // Bind based on type
        if ($k === ':assignee_id') {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
    }
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

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