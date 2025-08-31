<?php
/**
 * public/api/idp/list.php
 * List employee IDPs with status filters and pagination (GET)
 *
 * Query params:
 *   employee_id (optional) - defaults to current user or team for managers
 *   status (optional) - e.g., 'open','completed'
 *   page, per_page
 *
 * Response structure similar to other list endpoints.
 */
 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/IDRManager.php';

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

    $current = getCurrentUser();
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($current['employee_id'] ?? null);

    $employeeId = isset($_GET['employee_id']) && ctype_digit(strval($_GET['employee_id'])) ? intval($_GET['employee_id']) : null;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $page = isset($_GET['page']) && ctype_digit(strval($_GET['page'])) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) && ctype_digit(strval($_GET['per_page'])) ? max(1, min(100, intval($_GET['per_page']))) : 20;

    // Determine scope
    if ($employeeId === null) {
        if (isHRAdmin()) {
            // no restriction
        } elseif (isManager()) {
            require_once __DIR__ . '/../../classes/Employee.php';
            $empClass = new Employee();
            $team = $empClass->getTeamMembers(intval($currentEmployeeId));
            $employeeIds = array_map(function($e){ return intval($e['employee_id']); }, $team);
            $employeeIds[] = intval($currentEmployeeId);
        } else {
            $employeeId = intval($currentEmployeeId);
        }
    } else {
        if (!isHRAdmin() && !canAccessEmployee($employeeId)) {
            json_response(false, null, 'Insufficient permissions to view IDPs for this employee.', 403);
        }
    }

    // Build query
    $params = [];
    $where = [];

    if (isset($employeeIds) && is_array($employeeIds)) {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $where[] = "employee_id IN ($placeholders)";
        foreach ($employeeIds as $id) $params[] = $id;
    } elseif ($employeeId !== null) {
        $where[] = "employee_id = ?";
        $params[] = $employeeId;
    }

    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    $countSql = "SELECT COUNT(*) AS total FROM individual_development_plans $whereSql";
    $countRow = fetchOne($countSql, $params);
    $total = intval($countRow['total'] ?? 0);

    $offset = ($page - 1) * $perPage;
    $sql = "SELECT idp_id, employee_id, manager_id, career_goal, target_date, status, created_at, updated_at
            FROM individual_development_plans
            $whereSql
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?";

    $queryParams = $params;
    $queryParams[] = $perPage;
    $queryParams[] = $offset;

    $items = fetchAll($sql, $queryParams);

    // Attach activity counts summary
    foreach ($items as &$it) {
        $activities = fetchOne("SELECT COUNT(*) AS cnt FROM development_activities WHERE idp_id = ?", [$it['idp_id']]);
        $it['activity_count'] = intval($activities['cnt'] ?? 0);
    }

    $data = [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'items' => $items
    ];

    json_response(true, $data, 'IDPs retrieved.', 200);

} catch (Throwable $e) {
    error_log("IDP list endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}