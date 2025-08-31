<?php
/**
 * Create an Individual Development Plan (IDP)
 *
 * POST /public/api/idp/create.php
 *
 * Creates an IDP record in the individual_development_plans table.
 * Follows the same auth/response style as other public/api endpoints (achievements/kudos).
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';

 // CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Rate limit headers (placeholder)
header('X-RateLimit-Limit: 60');
header('X-RateLimit-Remaining: 59');
header('X-RateLimit-Reset: ' . (time() + 60));

try {
    requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new Exception('Invalid JSON payload');

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);
    $userId = $_SESSION['user_id'] ?? ($currentUser['user_id'] ?? null);

    // Determine employee for whom this IDP is created. Default to current employee.
    $employeeId = isset($input['employee_id']) ? (int)$input['employee_id'] : $currentEmployeeId;
    if (empty($employeeId)) {
        http_response_code(400);
        throw new Exception('employee_id is required (or you must be associated with an employee record)');
    }

    // Authorization: employees can only create their own IDP, while hr_admin and manager can create for others
    if ($userRole === 'employee' && $employeeId !== $currentEmployeeId) {
        http_response_code(403);
        throw new Exception('Employees can only create IDPs for themselves');
    }

    // Basic validation: career_goal required
    $careerGoal = isset($input['career_goal']) ? trim($input['career_goal']) : '';
    if ($careerGoal === '') {
        http_response_code(400);
        throw new Exception('Field career_goal is required');
    }

    $targetDate = isset($input['target_date']) && $input['target_date'] !== '' ? $input['target_date'] : null;
    $status = isset($input['status']) ? trim($input['status']) : 'draft';
    $periodId = isset($input['period_id']) && $input['period_id'] !== '' ? (int)$input['period_id'] : null;
    // Optionally allow passing manager_id, otherwise derive from employees.manager_id
    $managerId = isset($input['manager_id']) && $input['manager_id'] !== '' ? (int)$input['manager_id'] : null;

    // If no manager_id provided, try to lookup from employees table
    if ($managerId === null) {
        $uStmt = $db->prepare("SELECT manager_id FROM employees WHERE employee_id = :eid LIMIT 1");
        $uStmt->bindValue(':eid', $employeeId, PDO::PARAM_INT);
        $uStmt->execute();
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        $managerId = $uRow ? (isset($uRow['manager_id']) ? (int)$uRow['manager_id'] : null) : null;
    }

    $now = date('Y-m-d H:i:s');

    // Insert into individual_development_plans (schema from sql/004_comprehensive_enhancements.sql)
    $table = 'individual_development_plans';
    $stmt = $db->prepare("
        INSERT INTO {$table} (employee_id, manager_id, period_id, career_goal, target_date, status, created_at, updated_at)
        VALUES (:employee_id, :manager_id, :period_id, :career_goal, :target_date, :status, :created_at, :updated_at)
    ");

    $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
    if ($managerId === null) {
        $stmt->bindValue(':manager_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':manager_id', $managerId, PDO::PARAM_INT);
    }
    if ($periodId === null) {
        $stmt->bindValue(':period_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':career_goal', $careerGoal, PDO::PARAM_STR);
    if ($targetDate === null) {
        $stmt->bindValue(':target_date', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':target_date', $targetDate, PDO::PARAM_STR);
    }
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':created_at', $now, PDO::PARAM_STR);
    $stmt->bindValue(':updated_at', $now, PDO::PARAM_STR);

    $stmt->execute();
    $id = $db->lastInsertId();

    // Return the created record
    $select = $db->prepare("SELECT * FROM {$table} WHERE idp_id = :id LIMIT 1");
    $select->bindValue(':id', $id, PDO::PARAM_INT);
    $select->execute();
    $item = $select->fetch(PDO::FETCH_ASSOC);

    if (function_exists('logActivity')) {
        logActivity($userId, 'api_idp_create', 'idp', $id, null, [
            'employee_id' => $employeeId,
            'manager_id' => $managerId
        ]);
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => ['idp_id' => $id],
        'item' => $item,
        'message' => 'IDP created',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("IDP create API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    if (!headers_sent()) http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}