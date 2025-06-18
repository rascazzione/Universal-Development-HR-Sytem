<?php
/**
 * Evaluation Management Class
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';

class Evaluation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evaluation
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
            
            // Insert evaluation
            $sql = "INSERT INTO evaluations (employee_id, evaluator_id, period_id, status) VALUES (?, ?, ?, 'draft')";
            $evaluationId = insertRecord($sql, [
                $evaluationData['employee_id'],
                $evaluationData['evaluator_id'],
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
     * Update evaluation
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
            
            // Expected Results
            if (isset($evaluationData['expected_results'])) {
                $updateFields[] = "expected_results = ?";
                $params[] = json_encode($evaluationData['expected_results']);
                
                if (isset($evaluationData['expected_results_score'])) {
                    $updateFields[] = "expected_results_score = ?";
                    $params[] = $evaluationData['expected_results_score'];
                }
                
                if (isset($evaluationData['expected_results_weight'])) {
                    $updateFields[] = "expected_results_weight = ?";
                    $params[] = $evaluationData['expected_results_weight'];
                }
            }
            
            // Skills and Competencies
            if (isset($evaluationData['skills_competencies'])) {
                $updateFields[] = "skills_competencies = ?";
                $params[] = json_encode($evaluationData['skills_competencies']);
                
                if (isset($evaluationData['skills_competencies_score'])) {
                    $updateFields[] = "skills_competencies_score = ?";
                    $params[] = $evaluationData['skills_competencies_score'];
                }
                
                if (isset($evaluationData['skills_competencies_weight'])) {
                    $updateFields[] = "skills_competencies_weight = ?";
                    $params[] = $evaluationData['skills_competencies_weight'];
                }
            }
            
            // Key Responsibilities
            if (isset($evaluationData['key_responsibilities'])) {
                $updateFields[] = "key_responsibilities = ?";
                $params[] = json_encode($evaluationData['key_responsibilities']);
                
                if (isset($evaluationData['key_responsibilities_score'])) {
                    $updateFields[] = "key_responsibilities_score = ?";
                    $params[] = $evaluationData['key_responsibilities_score'];
                }
                
                if (isset($evaluationData['key_responsibilities_weight'])) {
                    $updateFields[] = "key_responsibilities_weight = ?";
                    $params[] = $evaluationData['key_responsibilities_weight'];
                }
            }
            
            // Living Values
            if (isset($evaluationData['living_values'])) {
                $updateFields[] = "living_values = ?";
                $params[] = json_encode($evaluationData['living_values']);
                
                if (isset($evaluationData['living_values_score'])) {
                    $updateFields[] = "living_values_score = ?";
                    $params[] = $evaluationData['living_values_score'];
                }
                
                if (isset($evaluationData['living_values_weight'])) {
                    $updateFields[] = "living_values_weight = ?";
                    $params[] = $evaluationData['living_values_weight'];
                }
            }
            
            // Overall evaluation
            if (isset($evaluationData['overall_rating'])) {
                $updateFields[] = "overall_rating = ?";
                $params[] = $evaluationData['overall_rating'];
            }
            
            if (isset($evaluationData['overall_comments'])) {
                $updateFields[] = "overall_comments = ?";
                $params[] = $evaluationData['overall_comments'];
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
                throw new Exception("No fields to update");
            }
            
            // Calculate overall rating if all section scores are provided
            $overallRating = $this->calculateOverallRating($evaluationData);
            if ($overallRating !== null) {
                $updateFields[] = "overall_rating = ?";
                $params[] = $overallRating;
            }
            
            $params[] = $evaluationId;
            $sql = "UPDATE evaluations SET " . implode(', ', $updateFields) . " WHERE evaluation_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            if ($affected > 0) {
                // Log evaluation update
                logActivity($_SESSION['user_id'] ?? null, 'evaluation_updated', 'evaluations', $evaluationId, $currentEvaluation, $evaluationData);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate overall rating based on weighted scores
     * @param array $evaluationData
     * @return float|null
     */
    private function calculateOverallRating($evaluationData) {
        $totalScore = 0;
        $totalWeight = 0;
        
        $sections = [
            'expected_results' => ['score' => 'expected_results_score', 'weight' => 'expected_results_weight', 'default_weight' => 40],
            'skills_competencies' => ['score' => 'skills_competencies_score', 'weight' => 'skills_competencies_weight', 'default_weight' => 25],
            'key_responsibilities' => ['score' => 'key_responsibilities_score', 'weight' => 'key_responsibilities_weight', 'default_weight' => 25],
            'living_values' => ['score' => 'living_values_score', 'weight' => 'living_values_weight', 'default_weight' => 10]
        ];
        
        foreach ($sections as $section => $config) {
            if (isset($evaluationData[$config['score']]) && $evaluationData[$config['score']] !== null) {
                $score = floatval($evaluationData[$config['score']]);
                $weight = isset($evaluationData[$config['weight']]) ? floatval($evaluationData[$config['weight']]) : $config['default_weight'];
                
                $totalScore += $score * ($weight / 100);
                $totalWeight += $weight;
            }
        }
        
        return $totalWeight > 0 ? round($totalScore, 2) : null;
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
        
        if ($evaluation) {
            // Decode JSON fields
            $jsonFields = ['expected_results', 'skills_competencies', 'key_responsibilities', 'living_values'];
            foreach ($jsonFields as $field) {
                if ($evaluation[$field]) {
                    $evaluation[$field] = json_decode($evaluation[$field], true);
                }
            }
        }
        
        return $evaluation;
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
     * Get evaluations for a specific employee
     * @param int $employeeId
     * @return array
     */
    public function getEmployeeEvaluations($employeeId) {
        $sql = "SELECT e.*, 
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.employee_id = ?
                ORDER BY p.start_date DESC";
        
        return fetchAll($sql, [$employeeId]);
    }
    
    /**
     * Get evaluations created by a specific evaluator
     * @param int $evaluatorId
     * @return array
     */
    public function getEvaluatorEvaluations($evaluatorId) {
        $sql = "SELECT e.*, 
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name, 
                       emp.employee_number, emp.position, emp.department,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluator_id = ?
                ORDER BY e.created_at DESC";
        
        return fetchAll($sql, [$evaluatorId]);
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
            
            // Delete evaluation
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
        
        // Average overall rating
        $sql = "SELECT AVG(overall_rating) as avg_rating FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause AND overall_rating IS NOT NULL";
        $result = fetchOne($sql, $params);
        $stats['average_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
        
        // Rating distribution
        $sql = "SELECT 
                    CASE 
                        WHEN overall_rating >= 4.5 THEN 'Excellent (4.5-5.0)'
                        WHEN overall_rating >= 3.5 THEN 'Good (3.5-4.4)'
                        WHEN overall_rating >= 2.5 THEN 'Satisfactory (2.5-3.4)'
                        WHEN overall_rating >= 1.5 THEN 'Needs Improvement (1.5-2.4)'
                        ELSE 'Unsatisfactory (1.0-1.4)'
                    END as rating_range,
                    COUNT(*) as count
                FROM evaluations e 
                JOIN employees emp ON e.employee_id = emp.employee_id 
                $whereClause AND overall_rating IS NOT NULL
                GROUP BY rating_range
                ORDER BY MIN(overall_rating) DESC";
        $result = fetchAll($sql, $params);
        $stats['rating_distribution'] = $result;
        
        return $stats;
    }
    
    /**
     * Get evaluation template structure
     * @return array
     */
    public function getEvaluationTemplate() {
        return [
            'expected_results' => [
                'title' => 'Expected Results',
                'weight' => 40,
                'criteria' => [
                    'achievement_of_objectives' => 'Achievement of Objectives',
                    'quality_of_work' => 'Quality of Work',
                    'productivity' => 'Productivity',
                    'initiative' => 'Initiative'
                ]
            ],
            'skills_competencies' => [
                'title' => 'Skills, Knowledge, and Competencies',
                'weight' => 25,
                'criteria' => [
                    'technical_skills' => 'Technical Skills',
                    'communication' => 'Communication',
                    'problem_solving' => 'Problem Solving',
                    'teamwork' => 'Teamwork'
                ]
            ],
            'key_responsibilities' => [
                'title' => 'Key Responsibilities',
                'weight' => 25,
                'criteria' => [
                    'job_knowledge' => 'Job Knowledge',
                    'responsibility_fulfillment' => 'Responsibility Fulfillment',
                    'reliability' => 'Reliability',
                    'adaptability' => 'Adaptability'
                ]
            ],
            'living_values' => [
                'title' => 'Living Our Values',
                'weight' => 10,
                'criteria' => [
                    'integrity' => 'Integrity',
                    'respect' => 'Respect',
                    'excellence' => 'Excellence',
                    'innovation' => 'Innovation'
                ]
            ]
        ];
    }
    
    /**
     * Add comment to evaluation
     * @param int $evaluationId
     * @param string $section
     * @param string $criterion
     * @param string $comment
     * @param int $userId
     * @return int|false
     */
    public function addComment($evaluationId, $section, $criterion, $comment, $userId) {
        try {
            $sql = "INSERT INTO evaluation_comments (evaluation_id, section, criterion, comment, created_by) VALUES (?, ?, ?, ?, ?)";
            return insertRecord($sql, [$evaluationId, $section, $criterion, $comment, $userId]);
        } catch (Exception $e) {
            error_log("Add comment error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get comments for evaluation
     * @param int $evaluationId
     * @return array
     */
    public function getEvaluationComments($evaluationId) {
        $sql = "SELECT c.*, u.username, e.first_name, e.last_name
                FROM evaluation_comments c
                JOIN users u ON c.created_by = u.user_id
                LEFT JOIN employees e ON u.user_id = e.user_id
                WHERE c.evaluation_id = ?
                ORDER BY c.created_at ASC";
        
        return fetchAll($sql, [$evaluationId]);
    }
}
?>