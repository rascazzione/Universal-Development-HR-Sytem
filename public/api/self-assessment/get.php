<?php
/**
 * Self-Assessment - Get Endpoint
 *
 * GET /public/api/self-assessment/get.php
 *
 * Retrieves self-assessments for employee/manager/admin with pagination and filters
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../../config/config.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// rate-limit headers (placeholder)
header('X-RateLimit-Limit: 60');
header('X-RateLimit-Remaining: 59');
header('X-RateLimit-Reset: ' . (time() + 60));

requireAuth();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method Not Allowed',
            'timestamp' => date('c')
        ]);
        exit;
    }

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    // Filters and pagination
    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $managerId = isset($_GET['manager_id']) ? (int)$_GET['manager_id'] : null;
    $periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'updated_at';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $manager = new SelfAssessmentManager();

    // Authorization rules
    // Employees: only view their own assessments
    // Managers: view direct reports (or team list)
    // HR Admin: view all
    $targetEmployee = $employeeId ?? $currentEmployeeId;

    if ($userRole === 'employee') {
        if ($targetEmployee !== $currentEmployeeId) {
            http_response_code(403);
            throw new Exception('Employees can only view their own self-assessments');
        }
    } elseif ($userRole === 'manager') {
        if ($employeeId && function_exists('isManagerOf')) {
            if (!isManagerOf($currentEmployeeId, $employeeId) && ($_SESSION['role'] ?? '') !== 'hr_admin') {
                http_response_code(403);
                throw new Exception('Managers can only view assessments of their direct reports');
            }
        }
    } elseif ($userRole === 'hr_admin') {
        // allowed
    } else {
        http_response_code(403);
        throw new Exception('Insufficient permissions');
    }

    // Build filters for manager class
    $filters = [];
    if ($targetEmployee) $filters['employee_id'] = $targetEmployee;
    if ($managerId) $filters['manager_id'] = $managerId;
    if ($periodId) $filters['period_id'] = $periodId;
    if ($status) $filters['status'] = $status;
    if ($search) $filters['search'] = $search;
    $filters['sort'] = $sort;
    $filters['order'] = $order;

    if (!method_exists($manager, 'listAssessments')) {
        throw new Exception('Assessment listing not available');
    }

    $assessments = $manager->listAssessments($filters, $limit, $offset);

    $total = null;
    if (method_exists($manager, 'countAssessments')) {
        $total = (int)$manager->countAssessments($filters);
    }

    $response = [
        'success' => true,
        'data' => [
            'assessments' => $assessments,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($assessments),
                'total' => $total
            ]
        ],
        'message' => 'Assessments retrieved',
        'timestamp' => date('c')
    ];

    if (function_exists('logActivity')) {
        logActivity($userId, 'api_self_assessment_list', 'self_assessment', null, null, ['filters' => $filters]);
    }

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Self-assessment get API error: " . $e->getMessage());
    if (!headers_sent()) http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}