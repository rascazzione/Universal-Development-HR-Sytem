<?php
/**
 * KudosManager Class
 * KUDOS Recognition System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';

class KudosManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Give kudos from one employee to another
     * @param int $giverId
     * @param int $receiverId
     * @param array $kudosData
     * @return int|false
     * @throws Exception
     */
    public function giveKudos($giverId, $receiverId, $kudosData) {
        try {
            // validate
            if (!is_numeric($giverId) || $giverId <= 0) throw new Exception("Invalid giverId");
            if (!is_numeric($receiverId) || $receiverId <= 0) throw new Exception("Invalid receiverId");
            if ($giverId == $receiverId) throw new Exception("Cannot give kudos to yourself");

            $sender = fetchOne("SELECT * FROM employees WHERE employee_id = ?", [$giverId]);
            $recipient = fetchOne("SELECT * FROM employees WHERE employee_id = ?", [$receiverId]);
            if (!$sender || !$recipient) throw new Exception("Sender or recipient not found");

            $message = sanitizeInput($kudosData['message'] ?? '');
            $categoryId = isset($kudosData['category_id']) ? intval($kudosData['category_id']) : null;
            $templateId = isset($kudosData['template_id']) ? intval($kudosData['template_id']) : null;
            $points = isset($kudosData['points_awarded']) ? intval($kudosData['points_awarded']) : 0;
            $isPublic = isset($kudosData['is_public']) ? (bool)$kudosData['is_public'] : true;

            if (empty($message) && empty($templateId)) throw new Exception("Message or template required");

            // If template provided, optionally load message
            if ($templateId) {
                $template = fetchOne("SELECT * FROM kudos_templates WHERE template_id = ? AND is_active = TRUE", [$templateId]);
                if ($template) {
                    if (empty($message)) $message = $template['message'];
                    if (!$categoryId && $template['category_id']) $categoryId = $template['category_id'];
                }
            }

            // Insert recognition
            $sql = "INSERT INTO kudos_recognitions (sender_employee_id, recipient_employee_id, category_id, template_id, message, points_awarded, is_public) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $kudosId = insertRecord($sql, [$giverId, $receiverId, $categoryId, $templateId, $message, $points, $isPublic ? 1 : 0]);

            // Update employee points summary (upsert)
            $this->incrementPoints($receiverId, $points);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'kudos_given', 'kudos_recognitions', $kudosId, null, $kudosData);

            // Create notification for recipient
            $notif = new NotificationManager();
            $recipientUser = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$receiverId]);
            $notif->createFromTemplate('kudos_received', $recipientUser['user_id'] ?? $receiverId, [
                'sender_name' => $sender['first_name'] . ' ' . $sender['last_name'],
                'message' => substr($message, 0, 140),
                'points' => $points
            ]);

            return $kudosId;
        } catch (Exception $e) {
            error_log("Give kudos error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * React to a kudos (like/celebrate/etc.)
     * @param int $kudosId
     * @param int $employeeId
     * @param string $reactionType
     * @return bool
     * @throws Exception
     */
    public function reactToKudos($kudosId, $employeeId, $reactionType) {
        try {
            $allowed = ['like','celebrate','insightful','support','love'];
            if (!in_array($reactionType, $allowed)) throw new Exception("Invalid reaction type");

            $kudos = fetchOne("SELECT * FROM kudos_recognitions WHERE kudos_id = ?", [$kudosId]);
            if (!$kudos) throw new Exception("Kudos not found");

            $existing = fetchOne("SELECT * FROM kudos_reactions WHERE kudos_id = ? AND reacting_employee_id = ?", [$kudosId, $employeeId]);
            if ($existing) {
                // toggle or update reaction
                if ($existing['reaction_type'] === $reactionType) {
                    // remove reaction
                    $del = updateRecord("DELETE FROM kudos_reactions WHERE reaction_id = ?", [$existing['reaction_id']]);
                    if ($del > 0) {
                        logActivity($_SESSION['user_id'] ?? null, 'kudos_reaction_removed', 'kudos_reactions', $existing['reaction_id'], $existing, null);
                        return true;
                    }
                    return false;
                } else {
                    $upd = updateRecord("UPDATE kudos_reactions SET reaction_type = ?, created_at = NOW() WHERE reaction_id = ?", [$reactionType, $existing['reaction_id']]);
                    if ($upd > 0) {
                        logActivity($_SESSION['user_id'] ?? null, 'kudos_reaction_updated', 'kudos_reactions', $existing['reaction_id'], $existing, ['reaction_type' => $reactionType]);
                        return true;
                    }
                    return false;
                }
            } else {
                $sql = "INSERT INTO kudos_reactions (kudos_id, reacting_employee_id, reaction_type) VALUES (?, ?, ?)";
                $rid = insertRecord($sql, [$kudosId, $employeeId, $reactionType]);
                logActivity($_SESSION['user_id'] ?? null, 'kudos_reaction_created', 'kudos_reactions', $rid, null, ['kudos_id' => $kudosId, 'reaction_type' => $reactionType]);
                return (bool)$rid;
            }
        } catch (Exception $e) {
            error_log("React to kudos error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get kudos feed with filters
     * @param array $filters
     * @return array
     */
    public function getKudosFeed($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['recipient_id'])) {
            $where .= " AND kr.recipient_employee_id = ?";
            $params[] = $filters['recipient_id'];
        }
        if (!empty($filters['sender_id'])) {
            $where .= " AND kr.sender_employee_id = ?";
            $params[] = $filters['sender_id'];
        }
        if (!empty($filters['category_id'])) {
            $where .= " AND kr.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['start_date'])) {
            $where .= " AND kr.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where .= " AND kr.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        if (isset($filters['is_public'])) {
            $where .= " AND kr.is_public = ?";
            $params[] = $filters['is_public'] ? 1 : 0;
        }

        $sql = "SELECT kr.*, 
                       se.first_name as sender_first, se.last_name as sender_last,
                       re.first_name as recipient_first, re.last_name as recipient_last,
                       (SELECT COUNT(*) FROM kudos_reactions r WHERE r.kudos_id = kr.kudos_id) as reaction_count
                FROM kudos_recognitions kr
                JOIN employees se ON kr.sender_employee_id = se.employee_id
                JOIN employees re ON kr.recipient_employee_id = re.employee_id
                $where
                ORDER BY kr.created_at DESC
                LIMIT ?";

        $limit = $filters['limit'] ?? 50;
        $params[] = $limit;

        return fetchAll($sql, $params);
    }

    /**
     * Calculate points for an employee in a period
     * @param int $employeeId
     * @param int $periodId
     * @return int
     */
    public function calculatePoints($employeeId, $periodId) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) return 0;
            $period = fetchOne("SELECT start_date, end_date FROM evaluation_periods WHERE period_id = ?", [$periodId]);
            if (!$period) return 0;

            $sql = "SELECT COALESCE(SUM(points_awarded),0) as total FROM kudos_recognitions WHERE recipient_employee_id = ? AND created_at BETWEEN ? AND ?";
            $result = fetchOne($sql, [$employeeId, $period['start_date'], $period['end_date']]);
            return intval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Calculate points error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Award badges based on thresholds (non-invasive - checks for table)
     * @param int $employeeId
     * @return array awarded badges
     */
    public function awardBadges($employeeId) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) return [];

            $pointsRow = fetchOne("SELECT total_points, monthly_points FROM employee_kudos_points WHERE employee_id = ?", [$employeeId]);
            $total = $pointsRow['total_points'] ?? 0;
            $monthly = $pointsRow['monthly_points'] ?? 0;

            $badges = [];
            $thresholds = [
                'bronze' => 100,
                'silver' => 250,
                'gold' => 500,
                'platinum' => 1000
            ];

            foreach ($thresholds as $name => $threshold) {
                if ($total >= $threshold) {
                    $badges[] = $name;
                }
            }

            // Persist badges if table exists
            try {
                $test = fetchOne("SELECT 1 FROM employee_badges LIMIT 1");
                // if no exception, table exists. Insert badges not already present.
                foreach ($badges as $badge) {
                    $exists = fetchOne("SELECT COUNT(*) as cnt FROM employee_badges WHERE employee_id = ? AND badge_key = ?", [$employeeId, $badge]);
                    if ($exists && $exists['cnt'] > 0) continue;
                    $sql = "INSERT INTO employee_badges (employee_id, badge_key, awarded_at) VALUES (?, ?, NOW())";
                    insertRecord($sql, [$employeeId, $badge]);
                    logActivity($_SESSION['user_id'] ?? null, 'badge_awarded', 'employee_badges', null, null, ['employee_id' => $employeeId, 'badge' => $badge]);
                }
            } catch (Exception $e) {
                // Table doesn't exist: skip persistence, log info
                error_log("employee_badges table not found, skipping persistence: " . $e->getMessage());
            }

            // Notify employee if badges awarded
            if (!empty($badges)) {
                $notif = new NotificationManager();
                $user = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employeeId]);
                $notif->createFromTemplate('badge_awarded', $user['user_id'] ?? $employeeId, [
                    'badges' => implode(', ', $badges)
                ]);
            }

            return $badges;
        } catch (Exception $e) {
            error_log("Award badges error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get leaderboard of top employees
     * @param string $type 'monthly'|'total'
     * @param int $limit
     * @return array
     */
    public function getLeaderboard($type = 'monthly', $limit = 10) {
        try {
            $column = $type === 'total' ? 'total_points' : 'monthly_points';
            $sql = "SELECT ekp.employee_id, ekp.total_points, ekp.monthly_points, e.first_name, e.last_name, e.position
                    FROM employee_kudos_points ekp
                    JOIN employees e ON ekp.employee_id = e.employee_id
                    ORDER BY $column DESC
                    LIMIT ?";
            return fetchAll($sql, [$limit]);
        } catch (Exception $e) {
            error_log("Get leaderboard error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Increment points for employee (internal)
     * @param int $employeeId
     * @param int $points
     * @return bool
     */
    private function incrementPoints($employeeId, $points) {
        try {
            if ($points <= 0) return true;

            // Upsert pattern: try update, if 0 rows then insert
            $sql = "UPDATE employee_kudos_points SET total_points = total_points + ?, monthly_points = monthly_points + ?, updated_at = NOW() WHERE employee_id = ?";
            $affected = updateRecord($sql, [$points, $points, $employeeId]);
            if ($affected > 0) return true;

            // Insert row if not exists
            $sql = "INSERT INTO employee_kudos_points (employee_id, total_points, monthly_points, last_reset_month) VALUES (?, ?, ?, ?)";
            $month = date('Y-m-01');
            insertRecord($sql, [$employeeId, $points, $points, $month]);
            return true;
        } catch (Exception $e) {
            error_log("Increment points error: " . $e->getMessage());
            return false;
        }
    }
}

?>