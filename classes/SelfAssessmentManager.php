<?php
/**
 * SelfAssessmentManager Class
 * Handles employee self-assessments for the Continuous Performance System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Evaluation.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/Employee.php';

class SelfAssessmentManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Create a new self assessment
     * @param int $employeeId
     * @param int $periodId
     * @param array $assessmentData
     * @return int
     * @throws Exception
     */
    public function createAssessment($employeeId, $periodId, $assessmentData) {
        try {
            // Validate inputs
            if (!is_numeric($employeeId) || $employeeId <= 0) {
                throw new Exception("Invalid employeeId");
            }
            if (!is_numeric($periodId) || $periodId <= 0) {
                throw new Exception("Invalid periodId");
            }
            if (empty($assessmentData) || !is_array($assessmentData)) {
                throw new Exception("assessmentData must be a non-empty array");
            }

            // Sanitize and validate required fields
            $dimension = sanitizeInput($assessmentData['dimension'] ?? '');
            $responses = $assessmentData['responses'] ?? null;
            if (empty($dimension) || empty($responses) || !is_array($responses)) {
                throw new Exception("dimension and responses are required");
            }

            // Compute overall score if not provided
            $overall = null;
            if (isset($assessmentData['overall_score'])) {
                $overall = floatval($assessmentData['overall_score']);
            } else {
                // calculate average of scores in responses
                $total = 0;
                $count = 0;
                foreach ($responses as $criterion => $r) {
                    if (isset($r['score']) && is_numeric($r['score'])) {
                        $total += floatval($r['score']);
                        $count++;
                    }
                }
                $overall = $count > 0 ? round($total / $count, 2) : null;
            }

            $sql = "INSERT INTO employee_self_assessments (employee_id, period_id, assessor_user_id, dimension, responses, overall_score, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')";
            $assessor = $_SESSION['user_id'] ?? null;
            $responsesJson = json_encode($responses);

            $insertId = insertRecord($sql, [$employeeId, $periodId, $assessor, $dimension, $responsesJson, $overall]);

            // Log and return
            logActivity($_SESSION['user_id'] ?? null, 'self_assessment_created', 'employee_self_assessments', $insertId, null, $assessmentData);

            return $insertId;
        } catch (Exception $e) {
            error_log("Create self assessment error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing assessment (only allowed in draft)
     * @param int $assessmentId
     * @param array $assessmentData
     * @return bool
     * @throws Exception
     */
    public function updateAssessment($assessmentId, $assessmentData) {
        try {
            if (!is_numeric($assessmentId) || $assessmentId <= 0) {
                throw new Exception("Invalid assessmentId");
            }

            $current = $this->getAssessmentById($assessmentId);
            if (!$current) {
                throw new Exception("Assessment not found");
            }

            if ($current['status'] !== 'draft') {
                throw new Exception("Only draft assessments can be updated");
            }

            $updateFields = [];
            $params = [];

            if (isset($assessmentData['responses']) && is_array($assessmentData['responses'])) {
                $updateFields[] = "responses = ?";
                $params[] = json_encode($assessmentData['responses']);

                // Recalculate overall score
                $total = 0;
                $count = 0;
                foreach ($assessmentData['responses'] as $r) {
                    if (isset($r['score']) && is_numeric($r['score'])) {
                        $total += floatval($r['score']);
                        $count++;
                    }
                }
                $overall = $count > 0 ? round($total / $count, 2) : null;
                $updateFields[] = "overall_score = ?";
                $params[] = $overall;
            }

            if (isset($assessmentData['dimension'])) {
                $updateFields[] = "dimension = ?";
                $params[] = sanitizeInput($assessmentData['dimension']);
            }

            if (isset($assessmentData['status'])) {
                $allowed = ['draft','submitted','approved','archived'];
                if (!in_array($assessmentData['status'], $allowed)) {
                    throw new Exception("Invalid status");
                }
                $updateFields[] = "status = ?";
                $params[] = $assessmentData['status'];
            }

            if (empty($updateFields)) {
                return true;
            }

            $params[] = $assessmentId;
            $sql = "UPDATE employee_self_assessments SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE self_assessment_id = ?";
            $affected = updateRecord($sql, $params);

            if ($affected > 0) {
                logActivity($_SESSION['user_id'] ?? null, 'self_assessment_updated', 'employee_self_assessments', $assessmentId, $current, $assessmentData);
            }

            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update self assessment error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit an assessment for manager/HR review
     * @param int $assessmentId
     * @return bool
     * @throws Exception
     */
    public function submitAssessment($assessmentId) {
        try {
            if (!is_numeric($assessmentId) || $assessmentId <= 0) {
                throw new Exception("Invalid assessmentId");
            }

            $assessment = $this->getAssessmentById($assessmentId);
            if (!$assessment) {
                throw new Exception("Assessment not found");
            }

            if ($assessment['status'] !== 'draft') {
                throw new Exception("Only draft assessments can be submitted");
            }

            $sql = "UPDATE employee_self_assessments SET status = 'submitted', submitted_at = NOW(), updated_at = NOW() WHERE self_assessment_id = ?";
            $affected = updateRecord($sql, [$assessmentId]);

            if ($affected > 0) {
                logActivity($_SESSION['user_id'] ?? null, 'self_assessment_submitted', 'employee_self_assessments', $assessmentId, $assessment, null);

                // Notify manager via NotificationManager if manager exists
                $employeeClass = new Employee();
                $employee = $employeeClass->getEmployeeById($assessment['employee_id']);
                if ($employee && !empty($employee['manager_id'])) {
                    $notif = new NotificationManager();
                    // Use the manager's user_id if available; fallback to manager employee_id for template functions that accept employee id
                    $managerUser = fetchOne("SELECT user_id FROM employees WHERE employee_id = ?", [$employee['manager_id']]);
                    $managerUserId = $managerUser['user_id'] ?? null;
                    // Create a simple notification record (template 'self_assessment_submitted' should exist in notification_templates)
                    $notif->createFromTemplate('self_assessment_submitted', $managerUserId ?? $employee['manager_id'], [
                        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                        'period_id' => $assessment['period_id']
                    ]);
                }
            }

            return $affected > 0;
        } catch (Exception $e) {
            error_log("Submit self assessment error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get assessments for an employee (optional period)
     * @param int $employeeId
     * @param int|null $periodId
     * @return array
     */
    public function getEmployeeAssessments($employeeId, $periodId = null) {
        $where = "WHERE employee_id = ?";
        $params = [$employeeId];
        if ($periodId) {
            $where .= " AND period_id = ?";
            $params[] = $periodId;
        }

        $sql = "SELECT * FROM employee_self_assessments $where ORDER BY created_at DESC";
        return fetchAll($sql, $params);
    }

    /**
     * Compare self-assessment with manager rating (if available)
     * @param int $assessmentId
     * @return array
     * @throws Exception
     */
    public function compareWithManagerRating($assessmentId) {
        try {
            $assessment = $this->getAssessmentById($assessmentId);
            if (!$assessment) {
                throw new Exception("Assessment not found");
            }

            // Try to find a manager evaluation for the same employee and period (evaluations table)
            $sql = "SELECT e.evidence_rating as manager_rating, e.evidence_summary as manager_summary
                    FROM evaluations e
                    WHERE e.employee_id = ? AND e.period_id = ? AND e.manager_id IS NOT NULL
                    LIMIT 1";

            $result = fetchOne($sql, [$assessment['employee_id'], $assessment['period_id']]);

            return [
                'self_overall' => $assessment['overall_score'],
                'manager_rating' => $result['manager_rating'] ?? null,
                'manager_summary' => $result['manager_summary'] ?? null,
                'difference' => (isset($result['manager_rating']) && is_numeric($assessment['overall_score']) ? round($assessment['overall_score'] - $result['manager_rating'], 2) : null)
            ];
        } catch (Exception $e) {
            error_log("Compare with manager rating error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: get assessment by id
     * @param int $assessmentId
     * @return array|false
     */
    public function getAssessmentById($assessmentId) {
        return fetchOne("SELECT * FROM employee_self_assessments WHERE self_assessment_id = ?", [$assessmentId]);
    }
}

?>