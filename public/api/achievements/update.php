<?php
/**
 * Achievement Journal - Update Endpoint
 *
 * PUT /public/api/achievements/update.php
 *
 * Updates an existing achievement entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';
require_once __DIR__ . '/../../classes/EvidenceManager.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';
require_once __DIR__ . '/../../config/config.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
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
    $method = $_SERVER['REQUEST_METHOD'];
    // Allow clients that can't send PUT to send POST with _method=PUT
    $override = $_POST['_method'] ?? $_GET['_method'] ?? null;
    if ($method !== 'PUT' && strtoupper($override) !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new Exception('Invalid JSON payload');

    if (empty($input['entry_id'])) {
        http_response_code(400);
        throw new Exception('Field entry_id is required');
    }

    $entryId = (int)$input['entry_id'];

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $journal = new AchievementJournal();
    $evidenceManager = new EvidenceManager();
    $notificationManager = new NotificationManager();

    if (!method_exists($journal, 'getEntryById')) {
        throw new Exception('Achievement retrieval not available');
    }

    $existing = $journal->getEntryById($entryId);
    if (!$existing) {
<thinking>
I'll add the Achievement Journal update endpoint following the established API patterns and using the existing classes: [`classes/AchievementJournal.php`](classes/AchievementJournal.php:1) and [`classes/EvidenceManager.php`](classes/EvidenceManager.php:1). This endpoint will enforce auth, role-based access, input validation, CORS, rate-limit headers, logging, and use prepared backend methods where available.
</thinking>

<write_to_file>
<path>public/api/achievements/update.php</path>
<content>
<?php
/**
 * Achievement Journal - Update Endpoint
 *
 * PUT /public/api/achievements/update.php
 *
 * Updates an achievement entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';
require_once __DIR__ . '/../../classes/EvidenceManager.php';
require_once __DIR__ . '/../../config/config.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
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
    $method = $_SERVER['REQUEST_METHOD'];
    $override = $_POST['_method'] ?? $_GET['_method'] ?? null;
    if ($method !== 'PUT' && strtoupper($override) !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed', 'timestamp' => date('c')]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new Exception('Invalid JSON payload');

    if (empty($input['entry_id'])) {
        http_response_code(400);
        throw new Exception('Field entry_id is required');
    }

    $entryId = (int)$input['entry_id'];
    $title = isset($input['title']) ? trim(filter_var($input['title'], FILTER_SANITIZE_STRING)) : null;
    $description = isset($input['description']) ? trim(filter_var($input['description'], FILTER_SANITIZE_STRING)) : null;
    $achievedAt = isset($input['achieved_at']) ? $input['achieved_at'] : null;
    $category = isset($input['category']) ? trim(filter_var($input['category'], FILTER_SANITIZE_STRING)) : null;
    $metadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : null;
    $evidenceIds = isset($input['evidence_ids']) && is_array($input['evidence_ids']) ? $input['evidence_ids'] : null;

    $currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
    $userId = $_SESSION['user_id'] ?? $currentUser['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? ($currentUser['role'] ?? 'employee');
    $currentEmployeeId = $_SESSION['employee_id'] ?? ($currentUser['employee_id'] ?? null);

    $journal = new AchievementJournal();
    $evidenceManager = new EvidenceManager();

    if (!method_exists($journal, 'getEntryById')) throw new Exception('Journal retrieval not available');
    $existing = $journal->getEntryById($entryId);
    if (!$existing) {
        http_response_code(404);
        throw new Exception('Achievement entry not found');
    }

    $ownerEmployeeId = (int)($existing['employee_id'] ?? 0);

    // Authorization: employees can update their own entries; managers/hr can update others
    if ($userRole === 'employee' && $ownerEmployeeId !== $currentEmployeeId) {
        http_response_code(403);
        throw new Exception('Employees can only update their own achievements');
    }

    // Prevent edits if locked
    if (($existing['status'] ?? 'active') === 'locked' && $userRole !== 'hr_admin') {
        http_response_code(403);
        throw new Exception('Cannot modify a locked achievement');
    }

    // Validate evidence if provided
    if (is_array($evidenceIds)) {
        foreach ($evidenceIds as $eId) {
            if (!method_exists($evidenceManager, 'getEvidenceById')) continue;
            $ev = $evidenceManager->getEvidenceById((int)$eId);
            if (!$ev) {
                http_response_code(400);
                throw new Exception("Evidence ID {$eId} not found");
            }
            if ((int)$ev['employee_id'] !== $ownerEmployeeId && $userRole !== 'hr_admin' && $userRole !== 'manager') {
                http_response_code(403);
                throw new Exception("Insufficient permissions to link evidence {$eId}");
            }
        }
    }

    $updatePayload = [
        'entry_id' => $entryId,
        'title' => $title,
        'description' => $description,
        'achieved_at' => $achievedAt,
        'category' => $category,
        'metadata' => $metadata,
        'updated_by' => $userId
    ];

    if (!method_exists($journal, 'updateEntry')) throw new Exception('Journal update not implemented');
    $success = $journal->updateEntry($updatePayload);

    // Update evidence links if provided
    if (is_array($evidenceIds) && method_exists($journal, 'linkEvidence')) {
        // Simple approach: unlink all existing then link provided
        if (method_exists($journal, 'unlinkAllEvidence')) {
            try { $journal->unlinkAllEvidence($entryId); } catch (Exception $ignore) {}
        }
        foreach ($evidenceIds as $eId) {
            try {
                $journal->linkEvidence($entryId, (int)$eId, $userId);
            } catch (Exception $le) {
                error_log("Failed to link evidence {$eId} to achievement {$entryId}: " . $le->getMessage());
            }
        }
    }

    if (function_exists('logActivity')) {
        logActivity($userId, 'api_achievement_update', 'achievement', $entryId, null, [
            'employee_id' => $ownerEmployeeId
        ]);
    }

    echo json_encode([
        'success' => true,
        'data' => ['entry_id' => $entryId],
        'message' => 'Achievement updated',
        'timestamp' => date('c')
    ]);
    exit;

} catch (Exception $e) {
    error_log("Achievement update API error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    if (!headers_sent()) http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit;
}