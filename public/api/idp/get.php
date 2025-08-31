<?php
/**
 * Get an Individual Development Plan (IDP)
 *
 * GET /public/api/idp/get.php?id={idp_id}
 *
 * Returns a single IDP record from individual_development_plans.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

try {
    requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        throw new Exception('Missing or invalid id parameter');
    }

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);
    $userId = $_SESSION['user_id'] ?? ($currentUser['user_id'] ?? null);

    $table = 'individual_development_plans';

    // Fetch the IDP
    $stmt = $db->prepare("SELECT * FROM {$table} WHERE idp_id = :id LIMIT 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        throw new Exception('Not found');
    }

    $ownerEmployeeId = isset($item['employee_id']) ? (int)$item['employee_id'] : null;
    $planManagerId = isset($item['manager_id']) ? (int)$item['manager_id'] : null;

    $allowed = false;
    // HR/admins can always access
    if ($userRole === 'hr_admin') {
        $allowed = true;
    }
    // Owner (employee) can access their own IDP
    if ($currentEmployeeId !== null && $currentEmployeeId === $ownerEmployeeId) {
        $allowed = true;
    }
    // The assigned manager can access
    if ($currentEmployeeId !== null && $planManagerId !== null && $currentEmployeeId === $planManagerId) {
        $allowed = true;
    }

    if (!$allowed) {
        http_response_code(403);
        throw new Exception('Forbidden');
    }

    // Optionally augment with related activities
    // (do not fail if related tables do not exist)
    try {
        $actStmt = $db->prepare("SELECT * FROM development_activities WHERE idp_id = :id ORDER BY created_at ASC");
        $actStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $actStmt->execute();
        $activities = $actStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $activities = [];
    }

    // Return item and optional activities
    echo json_encode([
        'success' => true,
        'item' => $item,
        'activities' => $activities,
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("IDP get API error: " . $e->getMessage());
    if (!headers_sent()) http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}