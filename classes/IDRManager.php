<?php
/**
 * IDRManager Class
 * Individual Development Plans Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';

class IDRManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Get IDPs for an employee
     * @param int $employeeId
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function getIDPs($employeeId, $filters = []) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) {
                throw new Exception("Invalid employee ID");
            }

            $params = [$employeeId];
            $where = ["employee_id = ?"];

            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['period_id'])) {
                $where[] = "period_id = ?";
                $params[] = $filters['period_id'];
            }

            if (!empty($filters['manager_id'])) {
                $where[] = "manager_id = ?";
                $params[] = $filters['manager_id'];
            }

            $whereClause = implode(" AND ", $where);
            
            $sql = "SELECT * FROM individual_development_plans WHERE $whereClause ORDER BY created_at DESC";
            $idps = fetchAll($sql, $params);

            // Enhance each IDP with activity counts and progress
            foreach ($idps as &$idp) {
                $activities = $this->getDevelopmentActivities($idp['idp_id']);
                $idp['activities'] = $activities;
                $idp['activity_count'] = count($activities);
                $idp['completed_activities'] = count(array_filter($activities, function($activity) {
                    return $this->getActivityProgress($activity['activity_id']) >= 100;
                }));
            }

            return $idps;
        } catch (Exception $e) {
            error_log("Get IDPs error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single IDP by ID
     * @param int $idpId
     * @return array
     * @throws Exception
     */
    public function getIDPById($idpId) {
        try {
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            $idp['activities'] = $this->getDevelopmentActivities($idpId);
            return $idp;
        } catch (Exception $e) {
            error_log("Get IDP by ID error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new IDP
     * @param int $employeeId
     * @param array $idpData
     * @return int
     * @throws Exception
     */
    public function createIDP($employeeId, $idpData) {
        try {
            // Validate required fields
            if (empty($idpData['career_goal'])) {
                throw new Exception("career_goal is required");
            }

            // Check if employee exists
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($employeeId);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            // Sanitize inputs
            $careerGoal = sanitizeInput($idpData['career_goal']);
            $managerId = $idpData['manager_id'] ?? $employee['manager_id'];
            $periodId = $idpData['period_id'] ?? null;
            $targetDate = $idpData['target_date'] ?? null;

            // Insert IDP
            $sql = "INSERT INTO individual_development_plans (employee_id, manager_id, period_id, career_goal, target_date) VALUES (?, ?, ?, ?, ?)";
            $idpId = insertRecord($sql, [
                $employeeId,
                $managerId,
                $periodId,
                $careerGoal,
                $targetDate
            ]);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'idp_created', 'individual_development_plans', $idpId, null, $idpData);

            return $idpId;
        } catch (Exception $e) {
            error_log("Create IDP error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an IDP
     * @param int $idpId
     * @param array $idpData
     * @return bool
     * @throws Exception
     */
    public function updateIDP($idpId, $idpData) {
        try {
            // Validate inputs
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            // Check if IDP exists
            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            $updateFields = [];
            $params = [];

            // Dynamic field updates
            $allowedFields = ['career_goal', 'manager_id', 'period_id', 'target_date', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($idpData[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $idpData[$field];
                }
            }

            if (empty($updateFields)) {
                return true; // No updates needed
            }

            $params[] = $idpId; // For WHERE clause
            $sql = "UPDATE individual_development_plans SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE idp_id = ?";
            $result = updateRecord($sql, $params);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'idp_updated', 'individual_development_plans', $idpId, $idp, $idpData);

            return $result > 0;
        } catch (Exception $e) {
            error_log("Update IDP error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add development activity to an IDP
     * @param int $idpId
     * @param array $activityData
     * @return int
     * @throws Exception
     */
    public function addDevelopmentActivity($idpId, $activityData) {
        try {
            // Validate inputs
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            if (empty($activityData['title'])) {
                throw new Exception("Activity title is required");
            }

            // Check if IDP exists
            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            // Sanitize inputs
            $title = sanitizeInput($activityData['title']);
            $activityType = in_array($activityData['activity_type'] ?? '', 
                ['training', 'course', 'project', 'mentoring', 'reading', 'certification', 'other']) 
                ? $activityData['activity_type'] : 'training';
            $provider = sanitizeInput($activityData['provider'] ?? '');
            $cost = isset($activityData['cost']) ? floatval($activityData['cost']) : null;
            $startDate = $activityData['start_date'] ?? null;
            $endDate = $activityData['end_date'] ?? null;
            $expectedOutcome = sanitizeInput($activityData['expected_outcome'] ?? '');
            $relatedKpiId = $activityData['related_kpi_id'] ?? null;

            // Insert development activity
            $sql = "INSERT INTO development_activities (idp_id, title, activity_type, provider, cost, start_date, end_date, expected_outcome, related_kpi_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $activityId = insertRecord($sql, [
                $idpId,
                $title,
                $activityType,
                $provider,
                $cost,
                $startDate,
                $endDate,
                $expectedOutcome,
                $relatedKpiId
            ]);

            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'activity_added', 'development_activities', $activityId, null, $activityData);

            return $activityId;
        } catch (Exception $e) {
            error_log("Add development activity error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get development activities for an IDP
     * @param int $idpId
     * @return array
     * @throws Exception
     */
    public function getDevelopmentActivities($idpId) {
        try {
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            $activities = fetchAll("SELECT * FROM development_activities WHERE idp_id = ? ORDER BY created_at DESC", [$idpId]);
            
            // Add progress information to each activity
            foreach ($activities as &$activity) {
                $activity['progress'] = $this->getActivityProgress($activity['activity_id']);
                $activity['progress_records'] = $this->getProgressRecords($activity['activity_id']);
            }

            return $activities;
        } catch (Exception $e) {
            error_log("Get development activities error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get progress for a specific activity
     * @param int $activityId
     * @return float
     */
    public function getActivityProgress($activityId) {
        try {
            $latestProgress = fetchOne("SELECT progress_percent FROM development_progress WHERE activity_id = ? ORDER BY created_at DESC LIMIT 1", [$activityId]);
            return $latestProgress ? floatval($latestProgress['progress_percent']) : 0.0;
        } catch (Exception $e) {
            error_log("Get activity progress error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get progress records for an activity
     * @param int $activityId
     * @return array
     */
    public function getProgressRecords($activityId) {
        try {
            return fetchAll("SELECT * FROM development_progress WHERE activity_id = ? ORDER BY created_at DESC", [$activityId]);
        } catch (Exception $e) {
            error_log("Get progress records error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Track progress for a development activity
     * @param int $activityId
     * @param array $progressData
     * @return bool
     * @throws Exception
     */
    public function trackProgress($activityId, $progressData) {
        try {
            // Validate inputs
            if (!is_numeric($activityId) || $activityId <= 0) {
                throw new Exception("Invalid activity ID");
            }

            $progressPercent = isset($progressData['progress_percent']) ? 
                max(0, min(100, floatval($progressData['progress_percent']))) : 0;
            $note = sanitizeInput($progressData['note'] ?? '');
            $evidence = isset($progressData['evidence']) ? json_encode($progressData['evidence']) : null;
            $updatedBy = $_SESSION['user_id'] ?? null;

            // Record progress
            $sql = "INSERT INTO development_progress (activity_id, updated_by_user_id, progress_percent, note, evidence) 
                    VALUES (?, ?, ?, ?, ?)";
            $progressId = insertRecord($sql, [
                $activityId,
                $updatedBy,
                $progressPercent,
                $note,
                $evidence
            ]);

            // If activity is completed, update the activity record
            if ($progressPercent == 100) {
                updateRecord("UPDATE development_activities SET updated_at = NOW() WHERE activity_id = ?", [$activityId]);
            }

            // Log activity
            logActivity($updatedBy, 'progress_tracked', 'development_activities', $activityId, null, $progressData);

            return $progressId > 0;
        } catch (Exception $e) {
            error_log("Track progress error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate ROI for an IDP
     * @param int $idpId
     * @return array
     * @throws Exception
     */
    public function calculateROI($idpId) {
        try {
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            // Get IDP and activities
            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            // Get all activities for this IDP
            $activities = fetchAll("SELECT * FROM development_activities WHERE idp_id = ?", [$idpId]);
            
            $totalCost = 0;
            $totalBenefit = 0;
            
            foreach ($activities as $activity) {
                $totalCost += floatval($activity['cost'] ?? 0);
                
                // Get ROI tracking records for this activity
                $roiRecords = fetchAll("SELECT * FROM development_roi_tracking WHERE activity_id = ?", [$activity['activity_id']]);
                
                foreach ($roiRecords as $roi) {
                    $benefitData = json_decode($roi['measured_benefit'], true);
                    $totalBenefit += floatval($benefitData['value'] ?? 0);
                }
            }

            // Calculate ROI
            $roi = $totalCost > 0 ? ($totalBenefit - $totalCost) / $totalCost : 0;

            return [
                'idp_id' => $idpId,
                'total_cost' => $totalCost,
                'total_benefit' => $totalBenefit,
                'roi_ratio' => round($roi * 100, 2), // Percentage
                'roi_description' => $this->getROIDescription($roi)
            ];
        } catch (Exception $e) {
            error_log("Calculate ROI error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate IDP report for employee
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function generateIDPReport($employeeId) {
        try {
            if (!is_numeric($employeeId) || $employeeId <= 0) {
                throw new Exception("Invalid employee ID");
            }

            // Get all IDPs for the employee
            $idps = fetchAll("SELECT * FROM individual_development_plans WHERE employee_id = ? ORDER BY created_at DESC", [$employeeId]);

            $report = [
                'employee_id' => $employeeId,
                'total_idps' => count($idps),
                'idps' => []
            ];

            foreach ($idps as $idp) {
                // Get activities for this IDP
                $activities = fetchAll("SELECT * FROM development_activities WHERE idp_id = ?", [$idp['idp_id']]);

                // Get progress for each activity
                $activitiesWithProgress = [];
                foreach ($activities as $activity) {
                    $progressRecords = fetchAll("SELECT * FROM development_progress WHERE activity_id = ? ORDER BY created_at DESC", [$activity['activity_id']]);
                    
                    $totalProgress = 0;
                    $latestProgress = null;
                    if (!empty($progressRecords)) {
                        $latestProgress = floatval($progressRecords[0]['progress_percent']);
                    }

                    $activity['latest_progress'] = $latestProgress;
                    $activity['total_progress_records'] = count($progressRecords);
                    
                    $activitiesWithProgress[] = $activity;
                }

                $idp['activities'] = $activitiesWithProgress;
                $idp['total_activities'] = count($activities);
                $idp['completed_activities'] = count(array_filter($activitiesWithProgress, function($a) {
                    return floatval($a['latest_progress']) >= 100;
                }));

                $report['idps'][] = $idp;
            }

            return $report;
        } catch (Exception $e) {
            error_log("Generate IDP report error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get ROI description based on ratio
     * @param float $roi
     * @return string
     */
    private function getROIDescription($roi) {
        if ($roi >= 2) {
            return "Excellent return on investment";
        } elseif ($roi >= 1) {
            return "Good return on investment";
        } elseif ($roi >= 0.5) {
            return "Moderate return on investment";
        } elseif ($roi >= 0) {
            return "Break-even investment";
        } else {
            return "Negative return on investment";
        }
    }

    /**
     * Update IDP status
     * @param int $idpId
     * @param string $status
     * @return bool
     */
    public function updateIDPStatus($idpId, $status) {
        try {
            // Validate inputs
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }
            
            $validStatuses = ['draft', 'active', 'on_hold', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            // Update IDP status
            $sql = "UPDATE individual_development_plans SET status = ?, updated_at = NOW() WHERE idp_id = ?";
            $result = updateRecord($sql, [$status, $idpId]);
            
            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'idp_status_updated', 'individual_development_plans', $idpId, null, ['status' => $status]);
            
            return $result > 0;
        } catch (Exception $e) {
            error_log("Update IDP status error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an IDP
     * @param int $idpId
     * @return bool
     * @throws Exception
     */
    public function deleteIDP($idpId) {
        try {
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            $result = updateRecord("DELETE FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            
            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'idp_deleted', 'individual_development_plans', $idpId, $idp, null);
            
            return $result > 0;
        } catch (Exception $e) {
            error_log("Delete IDP error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit an IDP for review/approval
     * @param int $idpId
     * @return bool
     * @throws Exception
     */
    public function submitIDP($idpId) {
        try {
            if (!is_numeric($idpId) || $idpId <= 0) {
                throw new Exception("Invalid IDP ID");
            }

            $idp = fetchOne("SELECT * FROM individual_development_plans WHERE idp_id = ?", [$idpId]);
            if (!$idp) {
                throw new Exception("IDP not found");
            }

            if ($idp['status'] === 'submitted') {
                return true; // Already submitted
            }

            $result = updateRecord("UPDATE individual_development_plans SET status = 'active', updated_at = NOW() WHERE idp_id = ?", [$idpId]);
            
            // Log activity
            logActivity($_SESSION['user_id'] ?? null, 'idp_submitted', 'individual_development_plans', $idpId, $idp, ['status' => 'active']);
            
            return $result > 0;
        } catch (Exception $e) {
            error_log("Submit IDP error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>