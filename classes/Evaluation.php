<?php
/**
 * Evidence-Based Evaluation Management Class
 * Continuous Performance System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';

class Evaluation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evidence-based evaluation
     * @param array $evaluationData
     * @return int|false
     */
    public function createEvaluation($evaluationData) {
        try {
            // Validate required fields
            $required = ['employee_id', 'evaluator_id', 'period_id'];
            foreach ($required as $field) {
                if (empty($evaluationData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check if evaluation already exists for this employee and period
            if ($this->evaluationExists($evaluationData['employee_id'], $evaluationData['period_id'])) {
                throw new Exception("Evaluation already exists for this employee and period");
            }
            
            // Get employee's manager
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($evaluationData['employee_id']);
            
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            // Get the employee's manager_id for direct relationship
            $managerId = $employee['manager_id'];
            if (empty($managerId)) {
                error_log("WARNING: Employee {$evaluationData['employee_id']} has no manager assigned");
            }
            
            // Insert evaluation with manager_id for direct relationship
            $sql = "INSERT INTO evaluations (employee_id, evaluator_id, manager_id, period_id, status)
                    VALUES (?, ?, ?, ?, 'draft')";
            $evaluationId = insertRecord($sql, [
                $evaluationData['employee_id'],
                $evaluationData['evaluator_id'],
                $managerId,
                $evaluationData['period_id']
            ]);
            
            // Log evaluation creation
            logActivity($_SESSION['user_id'] ?? null, 'evaluation_created', 'evaluations', $evaluationId, null, $evaluationData);
            
            return $evaluationId;
        } catch (Exception $e) {
            error_log("Create evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create evaluation from evidence journal
     * @param int $employeeId
     * @param int $periodId
     * @param int $evaluatorId
     * @return int
     */
    public function createFromEvidenceJournal(int $employeeId, int $periodId, int $evaluatorId): int {
        return $this->createEvaluation([
            'employee_id' => $employeeId,
            'evaluator_id' => $evaluatorId,
            'period_id' => $periodId
        ]);
    }
    
    /**
     * Aggregate evidence for an evaluation
     * @param int $evaluationId
     * @param int $employeeId
     * @param DateRange $period
     * @return bool
     */
    public function aggregateEvidence(int $evaluationId, int $employeeId, $period): bool {
        try {
            // Get evidence journal data for the period
            $journalClass = new GrowthEvidenceJournal();
            $evidenceByDimension = $journalClass->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
            
            // Store aggregated results
            foreach ($evidenceByDimension as $dimensionData) {
                $sql = "INSERT INTO evidence_evaluation_results 
                        (evaluation_id, dimension, evidence_count, avg_star_rating, total_positive_entries, total_negative_entries, calculated_score)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                insertRecord($sql, [
                    $evaluationId,
                    $dimensionData['dimension'],
                    $dimensionData['entry_count'],
                    round($dimensionData['avg_rating'], 2),
                    $dimensionData['positive_count'],
                    $dimensionData['negative_count'],
                    $this->calculateDimensionScore($dimensionData)
                ]);
            }
            
            // Update overall evaluation with evidence rating
            $overallRating = $this->calculateOverallEvidenceRating($evaluationId);
            $this->updateEvaluation($evaluationId, ['evidence_rating' => $overallRating]);
            
            return true;
        } catch (Exception $e) {
            error_log("Aggregate evidence error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate dimension score based on evidence
     * @param array $dimensionData
     * @return float
     */
    private function calculateDimensionScore(array $dimensionData): float {
        // Simple weighted calculation based on average rating and entry count
        $avgRating = $dimensionData['avg_rating'];
        $entryCount = $dimensionData['entry_count'];
        
        // Weight more heavily when there are more entries
        $weight = min(1.0, $entryCount / 10); // Max weight at 10 entries
        
        return round($avgRating * $weight, 2);
    }
    
    /**
     * Calculate overall evidence rating
     * @param int $evaluationId
     * @return float
     */
    public function calculateOverallEvidenceRating(int $evaluationId): float {
        try {
            $sql = "SELECT AVG(calculated_score) as avg_score FROM evidence_evaluation_results WHERE evaluation_id = ?";
            $result = fetchOne($sql, [$evaluationId]);
            
            return $result['avg_score'] ? round($result['avg_score'], 2) : 0;
        } catch (Exception $e) {
            error_log("Calculate overall evidence rating error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate evidence summary for evaluation
     * @param int $evaluationId
     * @return string
     */
    public function generateEvidenceSummary(int $evaluationId): string {
        try {
            $sql = "SELECT dimension, evidence_count, avg_star_rating, total_positive_entries, total_negative_entries
                    FROM evidence_evaluation_results 
                    WHERE evaluation_id = ? 
                    ORDER BY avg_star_rating DESC";
            
            $results = fetchAll($sql, [$evaluationId]);
            
            if (empty($results)) {
                return "No evidence entries found for this evaluation period.";
            }
            
            $summary = "Evidence-Based Evaluation Summary:\n\n";
            
            foreach ($results as $result) {
                $summary .= ucfirst($result['dimension']) . ":\n";
                $summary .= "  - Entries: " . $result['evidence_count'] . "\n";
                $summary .= "  - Average Rating: " . round($result['avg_star_rating'], 2) . "/5\n";
                $summary .= "  - Positive Feedback: " . $result['total_positive_entries'] . "\n";
                $summary .= "  - Areas for Improvement: " . $result['total_negative_entries'] . "\n\n";
            }
            
            return $summary;
        } catch (Exception $e) {
            error_log("Generate evidence summary error: " . $e->getMessage());
            return "Error generating evidence summary.";
        }
    }
    
    /**
     * Update evaluation status and metadata
     * @param int $evaluationId
     * @param array $evaluationData
     * @return bool
     */
    public function updateEvaluation($evaluationId, $evaluationData) {
        try {
            // Get current evaluation data for logging
            $currentEvaluation = $this->getEvaluationById($evaluationId);
            if (!$currentEvaluation) {
                throw new Exception("Evaluation not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Overall evaluation
            if (isset($evaluationData['evidence_summary'])) {
                $updateFields[] = "evidence_summary = ?";
                $params[] = $evaluationData['evidence_summary'];
            }
            
            if (isset($evaluationData['evidence_rating'])) {
                $updateFields[] = "evidence_rating = ?";
                $params[] = $evaluationData['evidence_rating'];
            }
            
            // Goals and development
            if (isset($evaluationData['goals_next_period'])) {
                $updateFields[] = "goals_next_period = ?";
                $params[] = $evaluationData['goals_next_period'];
            }
            
            if (isset($evaluationData['development_areas'])) {
                $updateFields[] = "development_areas = ?";
                $params[] = $evaluationData['development_areas'];
            }
            
            if (isset($evaluationData['strengths'])) {
                $updateFields[] = "strengths = ?";
                $params[] = $evaluationData['strengths'];
            }
            
            // Status
            if (isset($evaluationData['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $evaluationData['status'];
                
                // Set timestamps based on status
                if ($evaluationData['status'] === 'submitted') {
                    $updateFields[] = "submitted_at = NOW()";
                } elseif ($evaluationData['status'] === 'reviewed') {
                    $updateFields[] = "reviewed_at = NOW()";
                } elseif ($evaluationData['status'] === 'approved') {
                    $updateFields[] = "approved_at = NOW()";
                }
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            $params[] = $evaluationId;
            $sql = "UPDATE evaluations SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE evaluation_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            // Log evaluation update (even if no rows were affected, the operation was successful)
            logActivity($_SESSION['user_id'] ?? null, 'evaluation_updated', 'evaluations', $evaluationId, $currentEvaluation, $evaluationData);
            
            return true; // Return true if the query executed without errors, regardless of affected rows
        } catch (Exception $e) {
            error_log("Update evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evaluation by ID
     * @param int $evaluationId
     * @return array|false
     */
    public function getEvaluationById($evaluationId) {
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?";
        
        $evaluation = fetchOne($sql, [$evaluationId]);
        
        return $evaluation;
    }
    
    /**
     * Get evidence-based evaluation data
     * @param int $evaluationId
     * @return array|false
     */
    public function getEvidenceEvaluation(int $evaluationId) {
        $evaluation = $this->getEvaluationById($evaluationId);
        if (!$evaluation) {
            return false;
        }
        
        // Get evidence results by dimension
        $evaluation['evidence_results'] = $this->getEvidenceResults($evaluationId);
        
        // Get evidence summary
        $evaluation['evidence_summary_text'] = $this->generateEvidenceSummary($evaluationId);
        
        return $evaluation;
    }
    
    /**
     * Get evidence results for evaluation
     */
    private function getEvidenceResults(int $evaluationId): array {
        $sql = "SELECT * FROM evidence_evaluation_results WHERE evaluation_id = ? ORDER BY dimension";
        return fetchAll($sql, [$evaluationId]);
    }
    
    /**
     * Get evaluations with pagination and filtering
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getEvaluations($page = 1, $limit = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND e.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['evaluator_id'])) {
            $whereClause .= " AND e.evaluator_id = ?";
            $params[] = $filters['evaluator_id'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND emp.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (emp.first_name LIKE ? OR emp.last_name LIKE ? OR emp.employee_number LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, array_fill(0, 3, $searchTerm));
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM evaluations e
                     JOIN employees emp ON e.employee_id = emp.employee_id
                     $whereClause";
        $totalResult = fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Get evaluations
        $sql = "SELECT e.*, 
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name, 
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause 
                ORDER BY e.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $evaluations = fetchAll($sql, $params);
        
        return [
            'evaluations' => $evaluations,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Check if evaluation exists for employee and period
     * @param int $employeeId
     * @param int $periodId
     * @return bool
     */
    private function evaluationExists($employeeId, $periodId) {
        $sql = "SELECT COUNT(*) as count FROM evaluations WHERE employee_id = ? AND period_id = ?";
        $result = fetchOne($sql, [$employeeId, $periodId]);
        return $result['count'] > 0;
    }
    
    /**
     * Delete evaluation
     * @param int $evaluationId
     * @return bool
     */
    public function deleteEvaluation($evaluationId) {
        try {
            // Get current evaluation data for logging
            $evaluation = $this->getEvaluationById($evaluationId);
            if (!$evaluation) {
                throw new Exception("Evaluation not found");
            }
            
            // Delete evaluation (cascade will handle related records)
            $sql = "DELETE FROM evaluations WHERE evaluation_id = ?";
            $affected = updateRecord($sql, [$evaluationId]);
            
            if ($affected > 0) {
                // Log evaluation deletion
                logActivity($_SESSION['user_id'] ?? null, 'evaluation_deleted', 'evaluations', $evaluationId, $evaluation, null);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evaluation statistics
     * @param array $filters
     * @return array
     */
    public function getEvaluationStats($filters = []) {
        $stats = [];
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND emp.department = ?";
            $params[] = $filters['department'];
        }
        
        // Total evaluations
        $sql = "SELECT COUNT(*) as count FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause";
        $result = fetchOne($sql, $params);
        $stats['total_evaluations'] = $result['count'];
        
        // Evaluations by status
        $sql = "SELECT status, COUNT(*) as count FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause GROUP BY status";
        $result = fetchAll($sql, $params);
        $stats['by_status'] = [];
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Average evidence rating
        $sql = "SELECT AVG(evidence_rating) as avg_rating FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause AND evidence_rating IS NOT NULL";
        $result = fetchOne($sql, $params);
        $stats['average_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
        
        // Rating distribution
        $sql = "SELECT
                    CASE
                        WHEN evidence_rating >= 4.5 THEN 'Excellent (4.5-5.0)'
                        WHEN evidence_rating >= 3.5 THEN 'Good (3.5-4.4)'
                        WHEN evidence_rating >= 2.5 THEN 'Satisfactory (2.5-3.4)'
                        WHEN evidence_rating >= 1.5 THEN 'Needs Improvement (1.5-2.4)'
                        ELSE 'Unsatisfactory (1.0-1.4)'
                    END as rating_range,
                    COUNT(*) as count
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                $whereClause AND evidence_rating IS NOT NULL
                GROUP BY rating_range
                ORDER BY MIN(evidence_rating) DESC";
        $result = fetchAll($sql, $params);
        $stats['rating_distribution'] = $result;
        
        return $stats;
    }
    
    /**
     * Get evaluations where user is the evaluator
     * @param int $evaluatorId
     * @param array $filters
     * @return array
     */
    public function getEvaluatorEvaluations($evaluatorId, $filters = []) {
        $whereClause = "WHERE e.evaluator_id = ?";
        $params = [$evaluatorId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get evaluations for a specific manager (direct relationship)
     * @param int $managerId
     * @param array $filters
     * @return array
     */
    public function getManagerEvaluations($managerId, $filters = []) {
        $whereClause = "WHERE e.manager_id = ?";
        $params = [$managerId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND e.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       mgr.first_name as manager_first_name, mgr.last_name as manager_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                LEFT JOIN employees mgr ON e.manager_id = mgr.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        $evaluations = fetchAll($sql, $params);
        
        return $evaluations;
    }
    
    /**
     * Get evaluations for a specific employee (where they are being evaluated)
     * @param int $employeeId
     * @param array $filters
     * @return array
     */
    public function getEmployeeEvaluations($employeeId, $filters = []) {
        $whereClause = "WHERE e.employee_id = ?";
        $params = [$employeeId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        $sql = "SELECT e.*, e.evidence_rating,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        $evaluations = fetchAll($sql, $params);
        
        return $evaluations;
    }
    
    /**
     * Compatibility method for legacy job template-based evaluations
     * This method provides backward compatibility for existing evaluation pages
     * @param int $evaluationId
     * @return array|false
     */
    public function getJobTemplateEvaluation($evaluationId) {
        // First try to get the evaluation using the new evidence-based system
        $evaluation = $this->getEvaluationById($evaluationId);
        if (!$evaluation) {
            return false;
        }
        
        // Add evidence-based data to make it compatible with the edit page
        $evaluation['kpi_results'] = [];
        $evaluation['competency_results'] = [];
        $evaluation['responsibility_results'] = [];
        $evaluation['value_results'] = [];
        $evaluation['section_weights'] = [
            'kpis' => 25,
            'competencies' => 25,
            'responsibilities' => 25,
            'values' => 25
        ];
        
        // Get evidence results if they exist
        $evidenceResults = $this->getEvidenceResults($evaluationId);
        if (!empty($evidenceResults)) {
            // Convert evidence results to the format expected by the edit page
            foreach ($evidenceResults as $result) {
                // For backward compatibility, we'll create dummy entries that show evidence-based ratings
                switch ($result['dimension']) {
                    case 'kpis':
                        $evaluation['kpi_results'][] = [
                            'kpi_id' => 1, // dummy ID
                            'kpi_name' => 'Evidence-Based KPI Rating',
                            'category' => 'Performance',
                            'target_value' => 5.0,
                            'measurement_unit' => 'Stars',
                            'achieved_value' => $result['avg_star_rating'],
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'competencies':
                        $evaluation['competency_results'][] = [
                            'competency_id' => 1, // dummy ID
                            'competency_name' => 'Evidence-Based Competency Rating',
                            'category_name' => 'Performance',
                            'competency_type' => 'core',
                            'required_level' => 'advanced',
                            'achieved_level' => 'expert',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'responsibilities':
                        $evaluation['responsibility_results'][] = [
                            'responsibility_id' => 1, // dummy ID
                            'sort_order' => 1,
                            'responsibility_text' => 'Evidence-Based Responsibility Rating',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'values':
                        $evaluation['value_results'][] = [
                            'value_id' => 1, // dummy ID
                            'value_name' => 'Evidence-Based Value Rating',
                            'description' => 'Evidence-based company value assessment',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                }
            }
        }
        
        return $evaluation;
    }
    
    /**
     * Update KPI result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $kpiId
     * @param array $data
     * @return bool
     */
    public function updateKPIResult($evaluationId, $kpiId, $data) {
        // In the new evidence-based system, we don't have separate KPI results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Competency result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $competencyId
     * @param array $data
     * @return bool
     */
    public function updateCompetencyResult($evaluationId, $competencyId, $data) {
        // In the new evidence-based system, we don't have separate competency results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Responsibility result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $responsibilityId
     * @param array $data
     * @return bool
     */
    public function updateResponsibilityResult($evaluationId, $responsibilityId, $data) {
        // In the new evidence-based system, we don't have separate responsibility results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Value result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $valueId
     * @param array $data
     * @return bool
     */
    public function updateValueResult($evaluationId, $valueId, $data) {
        // In the new evidence-based system, we don't have separate value results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Check workflow status for an employee - compatibility method
     * @param int $employeeId
     * @return array
     */
    public function checkWorkflowStatus($employeeId) {
        // In the new evidence-based system, we don't have the same workflow checks
        // This is a compatibility method that returns a valid status
        return [
            'valid' => true,
            'step' => 'evaluation_ready',
            'message' => 'Evaluation system ready for evidence-based assessments',
            'action' => 'Proceed with evidence collection',
            'job_template_title' => 'Evidence-Based Evaluation'
        ];
    }
}
?>
