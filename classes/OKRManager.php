
<?php
/**
 * OKRManager Class
 * OKR Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';

class OKRManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Create a new OKR (Objective and Key Results)
     * @param int $employeeId
     * @param array $okrData
     * @return int
     * @throws Exception
     */
    public function createOKR($employeeId, $okrData) {
        try {
            // Validate required fields
            if (empty($okrData['title']) || empty($okrData['description'])) {
                throw new Exception("Title and description are required");
            }

            // Check if employee exists
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($employeeId);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            // Sanitize inputs
            $title = sanitizeInput($okrData['title']);
            $description = sanitizeInput($okrData['description']);
            $targetDate = $okrData['target_date'] ?? null;
            $confidence = in_array($okrData['confidence'] ?? '', ['low', 'medium', 'high']) ? $okrData['confidence'] : 'medium';
            $cycle = in_array($okrData['cycle'] ?? '', ['monthly', 'quarterly', 'annual']) ? $okrData['cycle'] : 'quarterly';
            $parentId = $okrData['parent_goal_id'] ?? null;
            
            // Initialize key results as JSON
            $keyResults = isset($okrData['key_results']) ? json_encode($okrData['key_results']) : null;

            // Insert OKR as a performance goal with OKR flags
            $sql = "INSERT INTO performance_goals (employee_id, title, description, target_date, okr_objective, okr_key_results, okr_owner, okr_confidence, okr_cycle, parent_goal_id) 
                    VALUES (?, ?, ?, ?, TRUE, ?, ?, ?, ?, ?)";
            
            $goalId = insertRecord($sql, [
                $employeeId, 
                $title, 
                $description, 
                $targetDate,
                $keyResults,
                $employeeId,
                $confidence,
                $cycle,
                $parentId
            ]);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'okr_created', 'performance_goals', $goalId, null, $okrData);

            // If this is a child OKR, create alignment
            if ($parentId) {
                $this->alignOKRs($goalId, $parentId);
            }

            return $goalId;
        } catch (Exception $e) {
            error_log("Create OKR error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Align child goal with parent goal
     * @param int $childGoalId
     * @param int $parentGoalId
     * @return bool
     * @throws Exception
     */
    public function alignOKRs($childGoalId, $parentGoalId) {
        try {
            // Validate inputs
            if (!is_numeric($childGoalId) || $childGoalId <= 0) {
                throw new Exception("Invalid child goal ID");
            }
            if (!is_numeric($parentGoalId) || $parentGoalId <= 0) {
                throw new Exception("Invalid parent goal ID");
            }

            // Check if both goals exist
            $childGoal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$childGoalId]);
            $parentGoal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$parentGoalId]);
            
            if (!$childGoal) {
                throw new Exception("Child goal not found");
            }
            if (!$parentGoal) {
                throw new Exception("Parent goal not found");
            }

            // Check if alignment already exists
            $existing = fetchOne("SELECT * FROM okr_alignments WHERE objective_goal_id = ? AND aligned_goal_id = ?", [$parentGoalId, $childGoalId]);
            if ($existing) {
                return true; // Already aligned
            }

            // Create alignment
            $sql = "INSERT INTO okr_alignments (objective_goal_id, aligned_goal_id, alignment_type) VALUES (?, ?, 'supports')";
            $alignmentId = insertRecord($sql, [$parentGoalId, $childGoalId]);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'okr_aligned', 'okr_alignments', $alignmentId, null, [
                'parent_goal_id' => $parentGoalId,
                'child_goal_id' => $childGoalId
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Align OKRs error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update progress for a goal
     * @param int $goalId
     * @param array $progressData
     * @return bool
     * @throws Exception
     */
    public function updateProgress($goalId, $progressData) {
        try {
            // Validate inputs
            if (!is_numeric($goalId) || $goalId <= 0) {
                throw new Exception("Invalid goal ID");
            }

            $progress = isset($progressData['progress']) ? floatval($progressData['progress']) : 0;
            $note = sanitizeInput($progressData['note'] ?? '');
            $updatedBy = $_SESSION['user_id'] ?? null;

            // Validate progress range
            if ($progress < 0 || $progress > 100) {
                throw new Exception("Progress must be between 0 and 100");
            }

            // Get current goal data for logging
            $currentGoal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$goalId]);
            if (!$currentGoal) {
                throw new Exception("Goal not found");
            }

            // Update progress in performance_goals table
            $sql = "UPDATE performance_goals SET okr_progress = ?, updated_at = NOW() WHERE goal_id = ?";
            $affected = updateRecord($sql, [$progress, $goalId]);

            if ($affected > 0) {
                // Record progress update in okr_progress_updates table
                $sql = "INSERT INTO okr_progress_updates (goal_id, updated_by_user_id, progress, note) VALUES (?, ?, ?, ?)";
                insertRecord($sql, [$goalId, $updatedBy, $progress, $note]);

                // Log activity
                logActivity($updatedBy, 'okr_progress_updated', 'performance_goals', $goalId, $currentGoal, $progressData);

                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Update progress error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Conduct a check-in for a goal
     * @param int $goalId
     * @param array $checkinData
     * @return int
     * @throws Exception
     */
    public function conductCheckin($goalId, $checkinData) {
        try {
            // Validate inputs
            if (!is_numeric($goalId) || $goalId <= 0) {
                throw new Exception("Invalid goal ID");
            }

            $checkinDate = $checkinData['checkin_date'] ?? date('Y-m-d');
            $note = sanitizeInput($checkinData['note'] ?? '');
            $progress = isset($checkinData['progress']) ? floatval($checkinData['progress']) : null;
            $conductedBy = $_SESSION['user_id'] ?? null;

            // Get current goal
            $goal = fetchOne("SELECT * FROM performance_goals WHERE goal_id = ?", [$goalId]);
            if (!$goal) {
                throw new Exception("Goal not found");
            }

            // Insert check-in record
            $sql = "INSERT INTO okr_checkins (goal_id, conducted_by_user_id, checkin_date, note, progress) VALUES (?, ?, ?, ?, ?)";
            $checkinId = insertRecord($sql, [$goalId, $conductedBy, $checkinDate, $note, $progress]);

            // Update goal progress if provided
            if ($progress !== null) {
                $this->updateProgress($goalId, ['progress' => $progress, 'note' => "Check-in on $checkinDate: $note"]);
            }

            // Log activity
            logActivity($conductedBy, 'okr_checkin_conducted', 'okr_checkins', $checkinId, null, $checkinData);

            return $checkinId;
        } catch (Exception $e) {
           