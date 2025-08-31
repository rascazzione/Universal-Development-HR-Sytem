
<?php
/**
 * IDPManager Class
 * Individual Development Plans Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';

class IDPManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
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
                $roiRecords = fetchAll("SELECT * FROM development_roi_tracking WHERE activity_id = ?", [$activity['activity_id']);
                
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