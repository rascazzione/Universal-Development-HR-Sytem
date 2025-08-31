<?php
/**
 * Achievement Journal - List Endpoint
 *
 * GET /public/api/achievements/list.php
 *
 * Returns a list of achievement entries with filters and pagination
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';
require_once __DIR__ . '/../../config/config.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Rate limit headers (placeholder)
header('X-RateLimit-Limit: 60');
header('X-RateLimit-Remaining: 59');
header('X-RateLimit-Reset: ' . (time() + 60));

requireAuth();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'achieved_at';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $journal = new AchievementJournal();

    // Authorization checks
    if ($userRole === 'employee') {
        // Can only view own entries
        if ($employeeId && $employeeId !== $currentEmployeeId) {
            http_response_code(403);
            throw new Exception('Employees can only view their own achievements');
        }
        $employeeId = $currentEmployeeId;
    } elseif ($userRole === 'manager') {
        if ($employeeId && function_exists('isManagerOf')) {
            if (!isManagerOf($currentEmployeeId, $employeeId) && ($_SESSION['role'] ?? '') !== 'hr_admin') {
                http_response_code(403);
                throw new Exception('Managers can only view achievements of their direct reports');
            }
        }
    } elseif ($userRole === 'hr_admin') {
        // allowed to view all
    } else {
        http_response_code(403);
        throw new Exception('Insufficient permissions');
    }

    // Build filters
    $filters = [];
    if ($employeeId) $filters['employee_id'] = $employeeId;
    if ($category) $filters['category'] = $category;
    if ($startDate) $filters['start_date'] = $startDate;
    if ($endDate) $filters['end_date'] = $endDate;
    if ($search) $filters['search'] = $search;
    $filters['sort'] = $sort;
    $filters['order'] = $order;

    if (!method_exists($journal, 'listEntries')) {
        throw new Exception('Journal listEntries not implemented');
    }

    $entries = $journal->listEntries($filters, $limit, $offset);

    $total = null;
    if (method_exists($journal, 'countEntries')) {
        $total = (int)$journal->countEntries($filters);
    }

    if (function_exists('logActivity')) {
        logActivity($userId, 'api_achievement_list', 'achievement', null, null, ['filters' => $filters]);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'entries' => $entries,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($entries),
                'total' => $total
            ]
        ],
        'message' => 'Achievement entries retrieved',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Achievement list API error: " . $e->getMessage());
    if (!headers_sent()) http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'timestamp' => date('c')]);
    exit;
}