<?php
/**
 * Notification Manager Class
 * Phase 3: Advanced Features - Notification System
 * Growth Evidence System
 */

require_once __DIR__ . '/../config/config.php';

class NotificationManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create a new notification
     * @param array $notificationData
     * @return int|false
     */
    public function createNotification($notificationData) {
        try {
            $required = ['user_id', 'type', 'title', 'message'];
            foreach ($required as $field) {
                if (empty($notificationData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $validTypes = ['feedback_submitted', 'evidence_reminder', 'evaluation_summary', 'milestone_alert', 'system_announcement'];
            if (!in_array($notificationData['type'], $validTypes)) {
                throw new Exception("Invalid notification type");
            }
            
            $sql = "INSERT INTO notifications (user_id, type, title, message, data, priority, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            return insertRecord($sql, [
                $notificationData['user_id'],
                $notificationData['type'],
                $notificationData['title'],
                $notificationData['message'],
                isset($notificationData['data']) ? json_encode($notificationData['data']) : null,
                $notificationData['priority'] ?? 'medium',
                $notificationData['expires_at'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create notification from template
     * @param string $templateKey
     * @param int $userId
     * @param array $variables
     * @param array $options
     * @return int|false
     */
    public function createFromTemplate($templateKey, $userId, $variables = [], $options = []) {
        try {
            // Get template
            $template = $this->getTemplate($templateKey);
            if (!$template) {
                throw new Exception("Template not found: $templateKey");
            }
            
            // Replace variables in title and message
            $title = $this->replaceVariables($template['title_template'], $variables);
            $message = $this->replaceVariables($template['message_template'], $variables);
            
            $notificationData = [
                'user_id' => $userId,
                'type' => $template['type'],
                'title' => $title,
                'message' => $message,
                'data' => $variables,
                'priority' => $options['priority'] ?? 'medium',
                'expires_at' => $options['expires_at'] ?? null
            ];
            
            return $this->createNotification($notificationData);
            
        } catch (Exception $e) {
            error_log("Create from template error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get notification template
     * @param string $templateKey
     * @return array|false
     */
    public function getTemplate($templateKey) {
        try {
            $sql = "SELECT * FROM notification_templates WHERE template_key = ? AND is_active = TRUE";
            return fetchOne($sql, [$templateKey]);
        } catch (Exception $e) {
            error_log("Get template error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Replace variables in template string
     * @param string $template
     * @param array $variables
     * @return string
     */
    private function replaceVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
    
    /**
     * Get user notifications
     * @param int $userId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserNotifications($userId, $filters = [], $limit = 20, $offset = 0) {
        try {
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            // Filter by read status
            if (isset($filters['is_read'])) {
                $whereClause .= " AND is_read = ?";
                $params[] = $filters['is_read'] ? 1 : 0;
            }
            
            // Filter by type
            if (!empty($filters['type'])) {
                $whereClause .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            // Filter by priority
            if (!empty($filters['priority'])) {
                $whereClause .= " AND priority = ?";
                $params[] = $filters['priority'];
            }
            
            // Exclude expired notifications
            if (!isset($filters['include_expired']) || !$filters['include_expired']) {
                $whereClause .= " AND (expires_at IS NULL OR expires_at > NOW())";
            }
            
            $sql = "SELECT * FROM notifications 
                    $whereClause 
                    ORDER BY priority DESC, created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            return fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get user notifications error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mark notification as read
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = TRUE, read_at = NOW() 
                    WHERE notification_id = ? AND user_id = ?";
            
            $affected = updateRecord($sql, [$notificationId, $userId]);
            return $affected > 0;
            
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mark all notifications as read for user
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notifications 
                    SET is_read = TRUE, read_at = NOW() 
                    WHERE user_id = ? AND is_read = FALSE";
            
            updateRecord($sql, [$userId]);
            return true;
            
        } catch (Exception $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete notification
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
            $affected = updateRecord($sql, [$notificationId, $userId]);
            return $affected > 0;
            
        } catch (Exception $e) {
            error_log("Delete notification error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get unread notification count
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = ? AND is_read = FALSE 
                    AND (expires_at IS NULL OR expires_at > NOW())";
            
            $result = fetchOne($sql, [$userId]);
            return $result['count'];
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send feedback submission notification
     * @param int $employeeId
     * @param array $evidenceData
     * @return bool
     */
    public function sendFeedbackNotification($employeeId, $evidenceData) {
        try {
            // Get employee user ID
            $employee = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee || !$employee['user_id']) {
                return false;
            }
            
            // Get manager name
            $manager = fetchOne("SELECT first_name, last_name FROM employees WHERE employee_id = ?", [$evidenceData['manager_id']]);
            $managerName = $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : 'Your manager';
            
            $variables = [
                'manager_name' => $managerName,
                'dimension' => ucfirst($evidenceData['dimension']),
                'rating' => $evidenceData['star_rating'],
                'content' => substr($evidenceData['content'], 0, 100) . '...'
            ];
            
            return $this->createFromTemplate('feedback_submitted', $employee['user_id'], $variables);
            
        } catch (Exception $e) {
            error_log("Send feedback notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send evidence reminder to managers
     * @param int $managerId
     * @param array $pendingEmployees
     * @return bool
     */
    public function sendEvidenceReminder($managerId, $pendingEmployees) {
        try {
            // Get manager user ID
            $manager = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$managerId]);
            if (!$manager || !$manager['user_id']) {
                return false;
            }
            
            $variables = [
                'pending_count' => count($pendingEmployees),
                'employee_names' => implode(', ', array_slice($pendingEmployees, 0, 3)) . (count($pendingEmployees) > 3 ? '...' : '')
            ];
            
            return $this->createFromTemplate('evidence_reminder', $manager['user_id'], $variables);
            
        } catch (Exception $e) {
            error_log("Send evidence reminder error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send evaluation period summary
     * @param int $employeeId
     * @param array $summaryData
     * @return bool
     */
    public function sendEvaluationSummary($employeeId, $summaryData) {
        try {
            // Get employee user ID
            $employee = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee || !$employee['user_id']) {
                return false;
            }
            
            $variables = [
                'period_name' => $summaryData['period_name'],
                'end_date' => $summaryData['end_date'],
                'evidence_count' => $summaryData['evidence_count'],
                'avg_rating' => number_format($summaryData['avg_rating'], 1)
            ];
            
            return $this->createFromTemplate('evaluation_summary', $employee['user_id'], $variables);
            
        } catch (Exception $e) {
            error_log("Send evaluation summary error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send milestone alert
     * @param int $employeeId
     * @param array $milestoneData
     * @return bool
     */
    public function sendMilestoneAlert($employeeId, $milestoneData) {
        try {
            // Get employee user ID
            $employee = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee || !$employee['user_id']) {
                return false;
            }
            
            $variables = [
                'milestone_title' => $milestoneData['milestone_title'],
                'goal_title' => $milestoneData['goal_title'],
                'due_date' => $milestoneData['due_date'],
                'status' => $milestoneData['status']
            ];
            
            $priority = 'medium';
            if (strtotime($milestoneData['due_date']) <= strtotime('+3 days')) {
                $priority = 'high';
            }
            if (strtotime($milestoneData['due_date']) <= strtotime('+1 day')) {
                $priority = 'urgent';
            }
            
            return $this->createFromTemplate('milestone_alert', $employee['user_id'], $variables, ['priority' => $priority]);
            
        } catch (Exception $e) {
            error_log("Send milestone alert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send system announcement to multiple users
     * @param array $userIds
     * @param string $title
     * @param string $content
     * @param string $priority
     * @param string $expiresAt
     * @return array
     */
    public function sendSystemAnnouncement($userIds, $title, $content, $priority = 'medium', $expiresAt = null) {
        try {
            $results = ['success' => 0, 'failed' => 0];
            
            foreach ($userIds as $userId) {
                try {
                    $variables = [
                        'announcement_title' => $title,
                        'announcement_content' => $content,
                        'priority' => $priority
                    ];
                    
                    $options = ['priority' => $priority];
                    if ($expiresAt) {
                        $options['expires_at'] = $expiresAt;
                    }
                    
                    if ($this->createFromTemplate('system_announcement', $userId, $variables, $options)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                } catch (Exception $e) {
                    $results['failed']++;
                    error_log("Failed to send announcement to user $userId: " . $e->getMessage());
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Send system announcement error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Clean up expired notifications
     * @return int Number of deleted notifications
     */
    public function cleanupExpiredNotifications() {
        try {
            $sql = "DELETE FROM notifications WHERE expires_at IS NOT NULL AND expires_at < NOW()";
            return updateRecord($sql, []);
            
        } catch (Exception $e) {
            error_log("Cleanup expired notifications error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get notification statistics
     * @param array $filters
     * @return array
     */
    public function getNotificationStatistics($filters = []) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];

            if (!empty($filters['start_date'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['end_date'];
            }

            $sql = "SELECT
                        type,
                        COUNT(*) as total_sent,
                        SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as total_read,
                        AVG(CASE WHEN is_read = TRUE THEN TIMESTAMPDIFF(MINUTE, created_at, read_at) ELSE NULL END) as avg_read_time_minutes
                    FROM notifications
                    $whereClause
                    GROUP BY type
                    ORDER BY total_sent DESC";

            return fetchAll($sql, $params);

        } catch (Exception $e) {
            error_log("Get notification statistics error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify evaluation period started
     * @param int $userId
     * @param int $periodId
     * @param int $evaluationId
     * @return bool
     */
    public function notifyEvaluationPeriodStarted($userId, $periodId, $evaluationId) {
        try {
            // Get period details
            $period = fetchOne("SELECT period_name, start_date, end_date FROM evaluation_periods WHERE period_id = ?", [$periodId]);
            if (!$period) return false;

            $variables = [
                'period_name' => $period['period_name'],
                'start_date' => formatDate($period['start_date']),
                'end_date' => formatDate($period['end_date'])
            ];

            return $this->createFromTemplate('evaluation_period_started', $userId, $variables);
        } catch (Exception $e) {
            error_log("Notify evaluation period started error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify self-evaluation submitted
     * @param int $employeeId
     * @param int $evaluationId
     * @return bool
     */
    public function notifySelfEvaluationSubmitted($employeeId, $evaluationId) {
        try {
            // Get employee details
            $employee = fetchOne("SELECT first_name, last_name FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee) return false;

            $variables = [
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name']
            ];

            // Notify manager
            $manager = fetchOne("SELECT user_id FROM employees WHERE employee_id = (SELECT manager_id FROM employees WHERE employee_id = ?)", [$employeeId]);
            if ($manager && $manager['user_id']) {
                return $this->createFromTemplate('self_evaluation_submitted', $manager['user_id'], $variables);
            }

            return false;
        } catch (Exception $e) {
            error_log("Notify self-evaluation submitted error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify manager of self-evaluation
     * @param int $managerUserId
     * @param int $evaluationId
     * @return bool
     */
    public function notifyManagerOfSelfEvaluation($managerUserId, $evaluationId) {
        try {
            // Get evaluation details
            $evaluation = fetchOne("
                SELECT e.*, emp.first_name, emp.last_name, p.period_name
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?
            ", [$evaluationId]);

            if (!$evaluation) return false;

            $variables = [
                'employee_name' => $evaluation['first_name'] . ' ' . $evaluation['last_name'],
                'period_name' => $evaluation['period_name'],
                'evaluation_id' => $evaluationId
            ];

            return $this->createFromTemplate('manager_self_evaluation_due', $managerUserId, $variables);
        } catch (Exception $e) {
            error_log("Notify manager of self-evaluation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify manager evaluation due
     * @param int $managerUserId
     * @param int $evaluationId
     * @return bool
     */
    public function notifyManagerEvaluationDue($managerUserId, $evaluationId) {
        try {
            // Get evaluation details
            $evaluation = fetchOne("
                SELECT e.*, emp.first_name, emp.last_name, p.period_name
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?
            ", [$evaluationId]);

            if (!$evaluation) return false;

            $variables = [
                'employee_name' => $evaluation['first_name'] . ' ' . $evaluation['last_name'],
                'period_name' => $evaluation['period_name'],
                'evaluation_id' => $evaluationId
            ];

            return $this->createFromTemplate('manager_evaluation_due', $managerUserId, $variables);
        } catch (Exception $e) {
            error_log("Notify manager evaluation due error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify final evaluation delivered
     * @param int $employeeId
     * @param int $evaluationId
     * @return bool
     */
    public function notifyFinalEvaluationDelivered($employeeId, $evaluationId) {
        try {
            // Get employee user ID
            $employee = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee || !$employee['user_id']) return false;

            // Get evaluation details
            $evaluation = fetchOne("
                SELECT e.*, p.period_name
                FROM evaluations e
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?
            ", [$evaluationId]);

            if (!$evaluation) return false;

            $variables = [
                'period_name' => $evaluation['period_name'],
                'evaluation_id' => $evaluationId
            ];

            return $this->createFromTemplate('final_evaluation_delivered', $employee['user_id'], $variables);
        } catch (Exception $e) {
            error_log("Notify final evaluation delivered error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify HR of evaluation completion
     * @param int $evaluationId
     * @return bool
     */
    public function notifyHROfEvaluationCompletion($evaluationId) {
        try {
            // Get HR admin users
            $hrUsers = fetchAll("SELECT user_id FROM users WHERE role = 'hr_admin'");

            if (empty($hrUsers)) return false;

            // Get evaluation details
            $evaluation = fetchOne("
                SELECT e.*, emp.first_name, emp.last_name, p.period_name
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?
            ", [$evaluationId]);

            if (!$evaluation) return false;

            $variables = [
                'employee_name' => $evaluation['first_name'] . ' ' . $evaluation['last_name'],
                'period_name' => $evaluation['period_name'],
                'evaluation_id' => $evaluationId
            ];

            $successCount = 0;
            foreach ($hrUsers as $hrUser) {
                if ($this->createFromTemplate('hr_evaluation_completed', $hrUser['user_id'], $variables)) {
                    $successCount++;
                }
            }

            return $successCount > 0;
        } catch (Exception $e) {
            error_log("Notify HR of evaluation completion error: " . $e->getMessage());
            return false;
        }
    }
}
?>