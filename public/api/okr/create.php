<?php
/**
 * public/api/okr/create.php
 * Create a new OKR (POST)
 *
 * POST JSON:
 * {
 *   "employee_id": 123,           // optional - defaults to current employee
 *   "title": "Objective title",   // required
 *   "description": "Desc",        // required
 *   "target_date": "YYYY-MM-DD",  // optional
 *   "confidence": "low|medium|high",
 *   "cycle": "monthly|quarterly|annual",
 *   "parent_goal_id": 12,         // optional for alignment
 *   "key_results": [ ... ]        // optional array
 * }
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": { "goal_id": 123 },
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
    // CORS preflight support
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, null, 'Method Not Allowed. Use POST.', 405);
    }

    if (!isAuthenticated()) {
        json_response(false, null, 'Authentication required.', 401);
    }

    // Read payload (support form and raw JSON)
    $input = $_POST;
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    $current = getCurrentUser();
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($current['employee_id'] ?? null);
    $userRole = $_SESSION['user_role'] ?? ($current['role'] ?? 'employee');
    $userId = $_SESSION['user_id'] ?? ($current['user_id'] ?? null);

    // Required fields
    if (empty($input['title']) || empty($input['description'])) {
        json_response(false, null, 'Fields title and description are required.', 400);
    }

    $employeeId = isset($input['employee_id']) && ctype_digit(strval($input['employee_id'])) ? intval($input['employee_id']) : intval($currentEmployeeId);
    if (empty($employeeId) || $employeeId <= 0) {
        json_response(false, null, 'Unable to determine target employee_id.', 400);
    }

    // Authorization:
    // - Employees may create their own OKRs
    // - Managers can create for their direct reports or themselves
    // - HR Admin can create for anyone
    if ($userRole === 'employee' && intval($employeeId) !== intval($currentEmployeeId)) {
        json_response(false, null, 'Employees can only create OKRs for themselves.', 403);
    }
    if ($userRole === 'manager' && intval($employeeId) !== intval($currentEmployeeId) && !isHRAdmin()) {
        // Managers must be creating for their direct reports; canAccessEmployee enforces this
        if (!canAccessEmployee($employeeId)) {
            json_response(false, null, 'Managers can only create OKRs for their direct reports.', 403);
        }
    }

    // Build okrData payload
    $okrData = [
        'title' => $input['title'],
        'description' => $input['description'],
        'target_date' => isset($input['target_date']) ? $input['target_date'] : null,
        'confidence' => isset($input['confidence']) ? $input['confidence'] : null,
        'cycle' => isset($input['cycle']) ? $input['cycle'] : null,
        'parent_goal_id' => isset($input['parent_goal_id']) && ctype_digit(strval($input['parent_goal_id'])) ? intval($input['parent_goal_id']) : null,
        'key_results' => isset($input['key_results']) && is_array($input['key_results']) ? $input['key_results'] : null
    ];

    $manager = new OKRManager();

    try {
        $goalId = $manager->createOKR($employeeId, $okrData);
    } catch (Exception $e) {
        json_response(false, null, 'Failed to create OKR: ' . $e->getMessage(), 500);
    }

    // Log activity (OKRManager already logs, but keep API-level log)
    if (function_exists('logActivity')) {
        logActivity($userId, 'api_okr_create', 'performance_goals', $goalId, null, ['employee_id' => $employeeId]);
    }

    json_response(true, ['goal_id' => $goalId], 'OKR created successfully.', 201);

} catch (Throwable $e) {
    error_log("OKR create endpoint error: " . $e->getMessage());
    json_response(false, null, 'Server error', 500);
}