<?php
/**
 * Notifications API Endpoints
 * Phase 3: Advanced Features - Notification System
 * Growth Evidence System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    $notificationManager = new NotificationManager();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($notificationManager, $action);
            break;
            
        case 'POST':
            handlePostRequest($notificationManager, $action);
            break;
            
        case 'PUT':
            handlePutRequest($notificationManager, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($notificationManager, $action);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($notificationManager, $action) {
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'list':
            $filters = [
                'is_read' => isset($_GET['is_read']) ? (bool)$_GET['is_read'] : null,
                'type' => $_GET['type'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'include_expired' => isset($_GET['include_expired'])
            ];
            
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = max(0, intval($_GET['offset'] ?? 0));
            
            $notifications = $notificationManager->getUserNotifications($userId, $filters, $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($notifications) === $limit
                ]
            ]);
            break;
            
        case 'unread_count':
            $count = $notificationManager->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'unread_count' => $count
            ]);
            break;
            
        case 'statistics':
            // Only allow HR admins to view statistics
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $filters = [
                'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
                'end_date' => $_GET['end_date'] ?? date('Y-m-t')
            ];
            
            $stats = $notificationManager->getNotificationStatistics($filters);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        case 'templates':
            // Only allow HR admins to view templates
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $templates = fetchAll("SELECT * FROM notification_templates WHERE is_active = TRUE ORDER BY type, template_key");
            
            echo json_encode([
                'success' => true,
                'templates' => $templates
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($notificationManager, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            // Only allow managers and HR admins to create notifications
            if (!in_array($_SESSION['role'], ['manager', 'hr_admin'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['user_id', 'type', 'title', 'message'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $notificationId = $notificationManager->createNotification($input);
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId
            ]);
            break;
            
        case 'create_from_template':
            // Only allow managers and HR admins
            if (!in_array($_SESSION['role'], ['manager', 'hr_admin'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['template_key', 'user_id', 'variables'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $options = [
                'priority' => $input['priority'] ?? 'medium',
                'expires_at' => $input['expires_at'] ?? null
            ];
            
            $notificationId = $notificationManager->createFromTemplate(
                $input['template_key'],
                $input['user_id'],
                $input['variables'],
                $options
            );
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId
            ]);
            break;
            
        case 'send_announcement':
            // Only allow HR admins to send announcements
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['user_ids', 'title', 'content'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $results = $notificationManager->sendSystemAnnouncement(
                $input['user_ids'],
                $input['title'],
                $input['content'],
                $input['priority'] ?? 'medium',
                $input['expires_at'] ?? null
            );
            
            echo json_encode([
                'success' => true,
                'results' => $results
            ]);
            break;
            
        case 'send_feedback_notification':
            // Automatically triggered when evidence is created
            $required = ['employee_id', 'evidence_data'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $success = $notificationManager->sendFeedbackNotification(
                $input['employee_id'],
                $input['evidence_data']
            );
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        case 'send_evidence_reminder':
            // Only allow HR admins and managers
            if (!in_array($_SESSION['role'], ['manager', 'hr_admin'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['manager_id', 'pending_employees'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $success = $notificationManager->sendEvidenceReminder(
                $input['manager_id'],
                $input['pending_employees']
            );
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        case 'send_evaluation_summary':
            // Only allow HR admins
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['employee_id', 'summary_data'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $success = $notificationManager->sendEvaluationSummary(
                $input['employee_id'],
                $input['summary_data']
            );
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        case 'send_milestone_alert':
            // Only allow managers and HR admins
            if (!in_array($_SESSION['role'], ['manager', 'hr_admin'])) {
                throw new Exception('Insufficient permissions');
            }
            
            $required = ['employee_id', 'milestone_data'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $success = $notificationManager->sendMilestoneAlert(
                $input['employee_id'],
                $input['milestone_data']
            );
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($notificationManager, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'mark_read':
            $notificationId = $input['notification_id'] ?? 0;
            
            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }
            
            $success = $notificationManager->markAsRead($notificationId, $userId);
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        case 'mark_all_read':
            $success = $notificationManager->markAllAsRead($userId);
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($notificationManager, $action) {
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'delete':
            $notificationId = $_GET['notification_id'] ?? 0;
            
            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }
            
            $success = $notificationManager->deleteNotification($notificationId, $userId);
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        case 'cleanup_expired':
            // Only allow HR admins
            if ($_SESSION['role'] !== 'hr_admin') {
                throw new Exception('Insufficient permissions');
            }
            
            $deletedCount = $notificationManager->cleanupExpiredNotifications();
            
            echo json_encode([
                'success' => true,
                'deleted_count' => $deletedCount
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Batch notification operations
 */
function handleBatchOperations($notificationManager, $input) {
    $userId = $_SESSION['user_id'];
    $operation = $input['operation'] ?? '';
    $notificationIds = $input['notification_ids'] ?? [];
    
    if (empty($notificationIds)) {
        throw new Exception('No notifications selected');
    }
    
    $results = ['success' => 0, 'failed' => 0];
    
    foreach ($notificationIds as $notificationId) {
        try {
            switch ($operation) {
                case 'mark_read':
                    if ($notificationManager->markAsRead($notificationId, $userId)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                case 'delete':
                    if ($notificationManager->deleteNotification($notificationId, $userId)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid batch operation');
            }
        } catch (Exception $e) {
            $results['failed']++;
        }
    }
    
    return $results;
}

/**
 * Get notification preferences for user
 */
function getUserNotificationPreferences($userId) {
    $preferences = fetchOne("SELECT notification_preferences FROM users WHERE user_id = ?", [$userId]);
    
    if ($preferences && $preferences['notification_preferences']) {
        return json_decode($preferences['notification_preferences'], true);
    }
    
    // Default preferences
    return [
        'email_notifications' => true,
        'browser_notifications' => true,
        'feedback_submitted' => true,
        'evidence_reminder' => true,
        'evaluation_summary' => true,
        'milestone_alert' => true,
        'system_announcement' => true
    ];
}

/**
 * Update notification preferences for user
 */
function updateUserNotificationPreferences($userId, $preferences) {
    $sql = "UPDATE users SET notification_preferences = ? WHERE user_id = ?";
    return updateRecord($sql, [json_encode($preferences), $userId]);
}

// Handle batch operations if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'batch') {
    $input = json_decode(file_get_contents('php://input'), true);
    $results = handleBatchOperations($notificationManager, $input);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    exit;
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'preferences') {
    $preferences = getUserNotificationPreferences($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'preferences' => $preferences
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'update_preferences') {
    $input = json_decode(file_get_contents('php://input'), true);
    $success = updateUserNotificationPreferences($_SESSION['user_id'], $input['preferences']);
    
    echo json_encode([
        'success' => $success
    ]);
    exit;
}
?>