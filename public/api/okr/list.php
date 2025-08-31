<?php
/**
 * public/api/okr/list.php
 * List employee OKRs with filters and pagination (GET)
 *
 * Query params:
 *   employee_id (optional) - defaults to current user (or team for managers)
 *   status (optional) - e.g., 'open','closed'
 *   search (optional) - text search on title/description
 *   page (optional) - page number (default 1)
 *   per_page (optional) - items per page (default 20)
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "total": 123,
 *     "page": 1,
 *     "per_page": 20,
 *     "items": [ ... ]
 *   },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 */
 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/OKRManager.php';

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
    $userRole = $_SESSION['user_role'] ?? ($current['role'] ?? 'employee');

    // Read params
    $employeeId = isset($_GET['employee_id']) && ctype_digit(strval($_GET['employee_id'])) ? intval($_GET['employee_id']) : null;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $page = isset($_GET['page']) && ctype_digit(strval($_GET['page'])) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) && ctype_digit(strval($_GET['per_page'])) ? max(1, min(100, intval($_GET['per_page']))) : 20;

    // Determine accessible employee scope
    if ($employeeId === null) {
        // Default: current user for employees, team for managers, all for HR
        if (isHRAdmin()) {
            // no restriction
        } elseif (isManager()) {
            // managers can query their team; we'll collect their employee ids or fallback to current employee id
            // For simplicity, if employee_id not provided, return current manager's own okrs and their team (via IN query)
            require_once __DIR__ . '/../../classes/Employee.php';
            $empClass = new Employee();
            $team = $empClass->getTeamMembers(intval($currentEmployeeId));
            $employeeIds = array_map(function($e){ return intval($e['employee_id']); }, $team);
            $employeeIds[] = intval($currentEmployeeId);
        } else {
            $employeeId = intval($currentEmployeeId);
        }
    } else {
        // If employeeId provided, check access
        if (!isHRAdmin() && !canAccessEmployee($employeeId)) {
            json_response(false, null, 'Insufficient permissions to view OKRs for this employee.', 403);
        }
    }

    // Build base query
    $params = [];
    $where = ["okr_objective = TRUE"];

    if (isset($employeeIds) && is_array($employeeIds)) {
        // Build IN clause
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $where[] = "employee_id IN ($placeholders)";
        foreach ($employeeIds as $id) $params[] = $id;
    } elseif ($employeeId !== null) {
        $where[] = "employee_id = ?";
        $params[] = $employeeId;
    }

    if (!empty($status)) {
        // Map common statuses to DB columns if needed (example uses a 'status' column)
        $where[] = "status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where[] = "(title LIKE ? OR description LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Count total
    $countSql = "SELECT COUNT(*) AS total FROM performance_goals $whereSql";
    $countRow = fetchOne($countSql, $params);
    $total = intval($countRow['total'] ?? 0);

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT goal_id, employee_id, title, description, target_date, okr_progress, okr_confidence, okr_cycle, parent_goal_id, created_at, updated_at
            FROM performance_goals
            $whereSql
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?";

    // Append pagination params
    $queryParams = $params;
    $queryParams[] = $perPage;
    $queryParams[] = $offset;

    $items = fetchAll($sql, $queryParams);

    $data = [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'items' => $items
    ];

    json_response(true, $data, 'OKRs retrieved.', 200);

} catch (Throwable $e) {
    error_log("OKR list endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}