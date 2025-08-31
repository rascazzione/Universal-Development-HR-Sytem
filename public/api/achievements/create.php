<?php
/**
 * Achievement Journal - Create Endpoint
 *
 * POST /public/api/achievements/create.php
 *
 * Creates an achievement entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';
require_once __DIR__ . '/../../classes/EvidenceManager.php';
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

requireAuth();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new Exception('Invalid JSON payload');

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $required = ['employee_id', 'title', 'description'];
    foreach ($required as $f) {
        if (empty($input[$f])) {
            http_response_code(400);
            throw new Exception("Field '$f' is required");
        }
    }

    $employeeId = (int)$input['employee_id'];
    $title = trim(filter_var($input['title'], FILTER_SANITIZE_STRING));
    $description = trim(filter_var($input['description'], FILTER_SANITIZE_STRING));
    $achievedAt = isset($input['achieved_at']) ? $input['achieved_at'] : date('Y-m-d');
    $category = isset($input['category']) ? trim(filter_var($input['category'], FILTER_SANITIZE_STRING)) : 'general';
    $metadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : null;
    $evidenceIds = isset($input['evidence_ids']) && is_array($input['evidence_ids']) ? $input['evidence_ids'] : [];

    // Authorization
    if ($userRole === 'employee' && $employeeId !== $currentEmployeeId) {
        http_response_code(403);
        throw new Exception('Employees can only create achievements for themselves');
    }

    $journal = new AchievementJournal();
    $evidenceManager = new EvidenceManager();
    $notificationManager = new NotificationManager();

    // Validate evidence ownership if provided
    foreach ($evidenceIds as $eId) {
        if (!method_exists($evidenceManager, 'getEvidenceById')) continue;
        $ev = $evidenceManager->getEvidenceById((int)$eId);
        if (!$ev) {
            http_response_code(400);
            throw new Exception("Evidence ID {$eId} not found");
        }
        if ((int)$ev['employee_id'] !== $employeeId && $userRole !== 'hr_admin' && $userRole !== 'manager') {
            http_response_code(403);
            throw new Exception("Insufficient permissions to link evidence {$eId}");
        }
    }

    $payload = [
        'employee_id' => $employeeId,
        'title' => $title,
        'description' => $description,
        'achieved_at' => $achievedAt,
        'category' => $category,
        'metadata' => $metadata,
        'created_by' => $userId
    ];

    if (!method_exists($journal, 'createEntry')) {
        throw new Exception('Achievement journal createEntry not implemented');
    }

    $entryId = $journal->createEntry($payload);

    // Link evidence if any
    if (!empty($evidenceIds) && method_exists($journal, 'linkEvidence')) {
        foreach ($evidenceIds as $eId) {
            try {
                $journal->linkEvidence($entryId, (int)$eId, $userId);
            } catch (Exception $le) {
                error_log("Failed to link evidence {$eId} to achievement {$entryId}: " . $le->getMessage());
            }
        }
    }

    // Notify manager if exists
    $managerId = null;
    if (method_exists($journal, 'getManagerForEmployee')) {
        $managerId = $journal->getManagerForEmployee($employeeId);
    }

    if ($managerId && method_exists($notificationManager, 'createNotification')) {
        try {
            $notificationManager->createNotification([
                'user_id' => $managerId,
                'type' => 'achievement_created',
                'title' => "Achievement added by employee #{$employeeId}",
                'message' => $title,
                'priority' => 'low'
            ]);
        } catch (Exception $notifyEx) {
            error_log("Failed to notify manager {$managerId} about achievement {$entryId}: " . $notifyEx->getMessage());
        }
    }

    if (function_exists('logActivity')) {
        logActivity($userId, 'api_achievement_create', 'achievement', $entryId, null, [
            'employee_id' => $employeeId,
            'evidence_linked' => count($evidenceIds)
        ]);
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => ['entry_id' => $entryId],
        'message' => 'Achievement entry created',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Achievement create API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    if (!headers_sent()) http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}