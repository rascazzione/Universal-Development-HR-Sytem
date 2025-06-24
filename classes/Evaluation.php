<?php
/**
 * Evaluation Management Class - Job Template Based
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';

class Evaluation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evaluation from job template
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
            
            // WORKFLOW VALIDATION: Check prerequisite steps
            $workflowValidation = $this->validateEvaluationWorkflow($evaluationData['employee_id']);
            if (!$workflowValidation['valid']) {
                error_log("WORKFLOW VALIDATION FAILED: " . json_encode($workflowValidation));
                throw new Exception($workflowValidation['message']);
            }
            
            // Get employee's job template
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($evaluationData['employee_id']);
            
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            // LOG: Employee and job template details
            error_log("EVALUATION CREATION - Employee ID: {$evaluationData['employee_id']}, Job Template ID: " . ($employee['job_template_id'] ?? 'NULL'));
            
            // Insert evaluation with job template reference
            $sql = "INSERT INTO evaluations (employee_id, evaluator_id, period_id, job_template_id, status)
                    VALUES (?, ?, ?, ?, 'draft')";
            $evaluationId = insertRecord($sql, [
                $evaluationData['employee_id'],
                $evaluationData['evaluator_id'],
                $evaluationData['period_id'],
                $employee['job_template_id']
            ]);
            
            // LOG: Evaluation creation details
            error_log("EVALUATION CREATION - Evaluation ID: $evaluationId created successfully");
            error_log("EVALUATION CREATION - Job Template ID for initialization: " . ($employee['job_template_id'] ?? 'NULL'));
            
            // Initialize evaluation from job template
            if ($employee['job_template_id']) {
                error_log("EVALUATION CREATION - Starting template initialization for Job Template ID: " . $employee['job_template_id']);
                $initResult = $this->initializeEvaluationFromTemplate($evaluationId, $employee['job_template_id']);
                error_log("EVALUATION CREATION - Template initialization result: " . ($initResult ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("EVALUATION CREATION - WARNING: No job template ID found for employee, skipping initialization");
            }
            
            // Log evaluation creation
            logActivity($_SESSION['user_id'] ?? null, 'evaluation_created', 'evaluations', $evaluationId, null, $evaluationData);
            
            return $evaluationId;
        } catch (Exception $e) {
            error_log("Create evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Public method to check workflow status (for UI validation)
     * @param int $employeeId
     * @return array
     */
    public function checkWorkflowStatus(int $employeeId): array {
        return $this->validateEvaluationWorkflow($employeeId);
    }
    
    /**
     * Validate evaluation workflow prerequisites
     * @param int $employeeId
     * @return array
     */
    private function validateEvaluationWorkflow(int $employeeId): array {
        try {
            // Step 1: Check if job templates exist in the system
            $jobTemplateCount = fetchOne("SELECT COUNT(*) as count FROM job_position_templates WHERE is_active = 1")['count'];
            if ($jobTemplateCount == 0) {
                return [
                    'valid' => false,
                    'step' => 'job_templates',
                    'message' => 'No job templates found. Please create job templates first before creating evaluations.',
                    'action' => 'Create job templates in Admin > Job Templates'
                ];
            }
            
            // Step 2: Check if employee exists and has job template assigned
            $employee = fetchOne("SELECT employee_id, job_template_id, first_name, last_name FROM employees WHERE employee_id = ?", [$employeeId]);
            if (!$employee) {
                return [
                    'valid' => false,
                    'step' => 'employee',
                    'message' => 'Employee not found.',
                    'action' => 'Verify employee exists'
                ];
            }
            
            if (empty($employee['job_template_id'])) {
                return [
                    'valid' => false,
                    'step' => 'job_assignment',
                    'message' => "Employee {$employee['first_name']} {$employee['last_name']} does not have a job template assigned. Please assign a job template to this employee before creating an evaluation.",
                    'action' => 'Edit employee and assign a job template'
                ];
            }
            
            // Step 3: Verify the assigned job template exists and is active
            $jobTemplate = fetchOne("SELECT id, position_title, is_active FROM job_position_templates WHERE id = ?", [$employee['job_template_id']]);
            if (!$jobTemplate) {
                return [
                    'valid' => false,
                    'step' => 'job_template_missing',
                    'message' => "The job template assigned to employee {$employee['first_name']} {$employee['last_name']} no longer exists.",
                    'action' => 'Assign a valid job template to this employee'
                ];
            }
            
            if (!$jobTemplate['is_active']) {
                return [
                    'valid' => false,
                    'step' => 'job_template_inactive',
                    'message' => "The job template '{$jobTemplate['position_title']}' assigned to employee {$employee['first_name']} {$employee['last_name']} is inactive.",
                    'action' => 'Activate the job template or assign a different one'
                ];
            }
            
            // Step 4: Check if job template has required components
            $templateComponents = $this->validateJobTemplateComponents($employee['job_template_id']);
            if (!$templateComponents['valid']) {
                return $templateComponents;
            }
            
            // All validations passed
            return [
                'valid' => true,
                'message' => 'All workflow prerequisites are met',
                'job_template_id' => $employee['job_template_id'],
                'job_template_title' => $jobTemplate['position_title']
            ];
            
        } catch (Exception $e) {
            error_log("Workflow validation error: " . $e->getMessage());
            return [
                'valid' => false,
                'step' => 'system_error',
                'message' => 'System error during workflow validation: ' . $e->getMessage(),
                'action' => 'Contact system administrator'
            ];
        }
    }
    
    /**
     * Validate job template has required components
     * @param int $jobTemplateId
     * @return array
     */
    private function validateJobTemplateComponents(int $jobTemplateId): array {
        try {
            // Check for KPIs
            $kpiCount = fetchOne("SELECT COUNT(*) as count FROM job_template_kpis WHERE job_template_id = ?", [$jobTemplateId])['count'];
            
            // Check for competencies
            $competencyCount = fetchOne("SELECT COUNT(*) as count FROM job_template_competencies WHERE job_template_id = ?", [$jobTemplateId])['count'];
            
            // Check for responsibilities
            $responsibilityCount = fetchOne("SELECT COUNT(*) as count FROM job_template_responsibilities WHERE job_template_id = ?", [$jobTemplateId])['count'];
            
            // Check for values
            $valueCount = fetchOne("SELECT COUNT(*) as count FROM job_template_values WHERE job_template_id = ?", [$jobTemplateId])['count'];
            
            $missingComponents = [];
            if ($kpiCount == 0) $missingComponents[] = 'KPIs';
            if ($competencyCount == 0) $missingComponents[] = 'Competencies';
            if ($responsibilityCount == 0) $missingComponents[] = 'Key Responsibilities';
            if ($valueCount == 0) $missingComponents[] = 'Company Values';
            
            if (!empty($missingComponents)) {
                $jobTemplate = fetchOne("SELECT position_title FROM job_position_templates WHERE id = ?", [$jobTemplateId]);
                return [
                    'valid' => false,
                    'step' => 'incomplete_template',
                    'message' => "Job template '{$jobTemplate['position_title']}' is missing required components: " . implode(', ', $missingComponents) . ". Please complete the job template configuration.",
                    'action' => 'Edit the job template and add missing components',
                    'missing_components' => $missingComponents
                ];
            }
            
            return ['valid' => true];
            
        } catch (Exception $e) {
            error_log("Job template component validation error: " . $e->getMessage());
            return [
                'valid' => false,
                'step' => 'component_validation_error',
                'message' => 'Error validating job template components: ' . $e->getMessage(),
                'action' => 'Contact system administrator'
            ];
        }
    }
    
    /**
     * Create evaluation from job template
     * @param int $employeeId
     * @param int $periodId
     * @param int $evaluatorId
     * @return int
     */
    public function createFromJobTemplate(int $employeeId, int $periodId, int $evaluatorId): int {
        return $this->createEvaluation([
            'employee_id' => $employeeId,
            'evaluator_id' => $evaluatorId,
            'period_id' => $periodId
        ]);
    }
    
    /**
     * Initialize evaluation components from job template
     * @param int $evaluationId
     * @param int $jobTemplateId
     * @return bool
     */
    private function initializeEvaluationFromTemplate(int $evaluationId, int $jobTemplateId): bool {
        try {
            error_log("TEMPLATE_INIT - Starting initialization for Evaluation ID: $evaluationId, Job Template ID: $jobTemplateId");
            
            $jobTemplateClass = new JobTemplate();
            $template = $jobTemplateClass->getCompleteJobTemplate($jobTemplateId);
            
            if (!$template) {
                error_log("TEMPLATE_INIT - ERROR: Failed to get complete job template for ID: $jobTemplateId");
                return false;
            }
            
            error_log("TEMPLATE_INIT - Template retrieved successfully");
            error_log("TEMPLATE_INIT - KPIs count: " . count($template['kpis'] ?? []));
            error_log("TEMPLATE_INIT - Competencies count: " . count($template['competencies'] ?? []));
            error_log("TEMPLATE_INIT - Responsibilities count: " . count($template['responsibilities'] ?? []));
            error_log("TEMPLATE_INIT - Values count: " . count($template['values'] ?? []));
            
            // Initialize KPI results
            foreach ($template['kpis'] as $kpi) {
                error_log("TEMPLATE_INIT - Initializing KPI: " . ($kpi['kpi_name'] ?? 'Unknown'));
                $this->initializeKPIResult($evaluationId, $kpi);
            }
            
            // Initialize competency results
            foreach ($template['competencies'] as $competency) {
                error_log("TEMPLATE_INIT - Initializing Competency: " . ($competency['competency_name'] ?? 'Unknown'));
                $this->initializeCompetencyResult($evaluationId, $competency);
            }
            
            // Initialize responsibility results
            foreach ($template['responsibilities'] as $responsibility) {
                error_log("TEMPLATE_INIT - Initializing Responsibility: " . substr($responsibility['responsibility_text'] ?? 'Unknown', 0, 50));
                $this->initializeResponsibilityResult($evaluationId, $responsibility);
            }
            
            // Initialize value results
            foreach ($template['values'] as $value) {
                error_log("TEMPLATE_INIT - Initializing Value: " . ($value['value_name'] ?? 'Unknown'));
                $this->initializeValueResult($evaluationId, $value);
            }
            
            // Initialize section weights
            error_log("TEMPLATE_INIT - Initializing section weights");
            $this->initializeSectionWeights($evaluationId, $template);
            
            error_log("TEMPLATE_INIT - Initialization completed successfully for Evaluation ID: $evaluationId");
            return true;
        } catch (Exception $e) {
            error_log("TEMPLATE_INIT - ERROR: " . $e->getMessage());
            error_log("TEMPLATE_INIT - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Initialize KPI result
     */
    private function initializeKPIResult(int $evaluationId, array $kpi): void {
        try {
            $sql = "INSERT INTO evaluation_kpi_results (evaluation_id, kpi_id, target_value, weight_percentage)
                    VALUES (?, ?, ?, ?)";
            $result = insertRecord($sql, [
                $evaluationId,
                $kpi['kpi_id'],
                $kpi['target_value'],
                $kpi['weight_percentage']
            ]);
            error_log("TEMPLATE_INIT - KPI result created with ID: $result for KPI: " . ($kpi['kpi_name'] ?? 'Unknown'));
        } catch (Exception $e) {
            error_log("TEMPLATE_INIT - ERROR creating KPI result: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Initialize competency result
     */
    private function initializeCompetencyResult(int $evaluationId, array $competency): void {
        try {
            $sql = "INSERT INTO evaluation_competency_results (evaluation_id, competency_id, required_level, weight_percentage)
                    VALUES (?, ?, ?, ?)";
            $result = insertRecord($sql, [
                $evaluationId,
                $competency['competency_id'],
                $competency['required_level'],
                $competency['weight_percentage']
            ]);
            error_log("TEMPLATE_INIT - Competency result created with ID: $result for Competency: " . ($competency['competency_name'] ?? 'Unknown'));
        } catch (Exception $e) {
            error_log("TEMPLATE_INIT - ERROR creating competency result: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Initialize responsibility result
     */
    private function initializeResponsibilityResult(int $evaluationId, array $responsibility): void {
        try {
            $sql = "INSERT INTO evaluation_responsibility_results (evaluation_id, responsibility_id, weight_percentage)
                    VALUES (?, ?, ?)";
            $result = insertRecord($sql, [
                $evaluationId,
                $responsibility['id'],
                $responsibility['weight_percentage']
            ]);
            error_log("TEMPLATE_INIT - Responsibility result created with ID: $result for Responsibility ID: " . ($responsibility['id'] ?? 'Unknown'));
        } catch (Exception $e) {
            error_log("TEMPLATE_INIT - ERROR creating responsibility result: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Initialize value result
     */
    private function initializeValueResult(int $evaluationId, array $value): void {
        try {
            $sql = "INSERT INTO evaluation_value_results (evaluation_id, value_id, weight_percentage)
                    VALUES (?, ?, ?)";
            $result = insertRecord($sql, [
                $evaluationId,
                $value['value_id'],
                $value['weight_percentage']
            ]);
            error_log("TEMPLATE_INIT - Value result created with ID: $result for Value: " . ($value['value_name'] ?? 'Unknown'));
        } catch (Exception $e) {
            error_log("TEMPLATE_INIT - ERROR creating value result: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Initialize section weights
     */
    private function initializeSectionWeights(int $evaluationId, array $template): void {
        $weights = $this->calculateSectionWeights($template);
        
        foreach ($weights as $section => $weight) {
            $sql = "INSERT INTO evaluation_section_weights (evaluation_id, section_type, weight_percentage) 
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE weight_percentage = VALUES(weight_percentage)";
            updateRecord($sql, [$evaluationId, $section, $weight]);
        }
    }
    
    /**
     * Calculate section weights based on template
     */
    private function calculateSectionWeights(array $template): array {
        $totalComponents = count($template['kpis']) + count($template['competencies']) + 
                          count($template['responsibilities']) + count($template['values']);
        
        if ($totalComponents === 0) {
            return [
                'kpis' => 25.00,
                'competencies' => 25.00,
                'responsibilities' => 25.00,
                'values' => 25.00
            ];
        }
        
        return [
            'kpis' => count($template['kpis']) > 0 ? 40.00 : 0.00,
            'competencies' => count($template['competencies']) > 0 ? 25.00 : 0.00,
            'responsibilities' => count($template['responsibilities']) > 0 ? 25.00 : 0.00,
            'values' => count($template['values']) > 0 ? 10.00 : 0.00
        ];
    }
    
    /**
     * Update KPI result
     * @param int $evaluationId
     * @param int $kpiId
     * @param array $data
     * @return bool
     */
    public function updateKPIResult(int $evaluationId, int $kpiId, array $data): bool {
        try {
            $sql = "UPDATE evaluation_kpi_results 
                    SET achieved_value = ?, score = ?, comments = ?, updated_at = NOW()
                    WHERE evaluation_id = ? AND kpi_id = ?";
            
            $affected = updateRecord($sql, [
                $data['achieved_value'] ?? null,
                $data['score'] ?? null,
                $data['comments'] ?? null,
                $evaluationId,
                $kpiId
            ]);
            
            if ($affected > 0) {
                $this->updateOverallRating($evaluationId);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update KPI result error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update competency result
     * @param int $evaluationId
     * @param int $competencyId
     * @param array $data
     * @return bool
     */
    public function updateCompetencyResult(int $evaluationId, int $competencyId, array $data): bool {
        try {
            $sql = "UPDATE evaluation_competency_results 
                    SET achieved_level = ?, score = ?, comments = ?, updated_at = NOW()
                    WHERE evaluation_id = ? AND competency_id = ?";
            
            $affected = updateRecord($sql, [
                $data['achieved_level'] ?? null,
                $data['score'] ?? null,
                $data['comments'] ?? null,
                $evaluationId,
                $competencyId
            ]);
            
            if ($affected > 0) {
                $this->updateOverallRating($evaluationId);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update competency result error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update responsibility result
     * @param int $evaluationId
     * @param int $responsibilityId
     * @param array $data
     * @return bool
     */
    public function updateResponsibilityResult(int $evaluationId, int $responsibilityId, array $data): bool {
        try {
            $sql = "UPDATE evaluation_responsibility_results 
                    SET score = ?, comments = ?, updated_at = NOW()
                    WHERE evaluation_id = ? AND responsibility_id = ?";
            
            $affected = updateRecord($sql, [
                $data['score'] ?? null,
                $data['comments'] ?? null,
                $evaluationId,
                $responsibilityId
            ]);
            
            if ($affected > 0) {
                $this->updateOverallRating($evaluationId);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update responsibility result error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update value result
     * @param int $evaluationId
     * @param int $valueId
     * @param array $data
     * @return bool
     */
    public function updateValueResult(int $evaluationId, int $valueId, array $data): bool {
        try {
            $sql = "UPDATE evaluation_value_results 
                    SET score = ?, comments = ?, updated_at = NOW()
                    WHERE evaluation_id = ? AND value_id = ?";
            
            $affected = updateRecord($sql, [
                $data['score'] ?? null,
                $data['comments'] ?? null,
                $evaluationId,
                $valueId
            ]);
            
            if ($affected > 0) {
                $this->updateOverallRating($evaluationId);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Update value result error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate template-based overall score
     * @param int $evaluationId
     * @return float
     */
    public function calculateTemplateBasedScore(int $evaluationId): float {
        try {
            $totalScore = 0;
            $totalWeight = 0;
            
            // Get section weights
            $sectionWeights = $this->getSectionWeights($evaluationId);
            
            // Calculate KPI scores
            $kpiScore = $this->calculateKPIScore($evaluationId);
            if ($kpiScore['count'] > 0) {
                $totalScore += $kpiScore['average_score'] * ($sectionWeights['kpis'] / 100);
                $totalWeight += $sectionWeights['kpis'];
            }
            
            // Calculate competency scores
            $competencyScore = $this->calculateCompetencyScore($evaluationId);
            if ($competencyScore['count'] > 0) {
                $totalScore += $competencyScore['average_score'] * ($sectionWeights['competencies'] / 100);
                $totalWeight += $sectionWeights['competencies'];
            }
            
            // Calculate responsibility scores
            $responsibilityScore = $this->calculateResponsibilityScore($evaluationId);
            if ($responsibilityScore['count'] > 0) {
                $totalScore += $responsibilityScore['average_score'] * ($sectionWeights['responsibilities'] / 100);
                $totalWeight += $sectionWeights['responsibilities'];
            }
            
            // Calculate value scores
            $valueScore = $this->calculateValueScore($evaluationId);
            if ($valueScore['count'] > 0) {
                $totalScore += $valueScore['average_score'] * ($sectionWeights['values'] / 100);
                $totalWeight += $sectionWeights['values'];
            }
            
            return $totalWeight > 0 ? round($totalScore, 2) : 0;
        } catch (Exception $e) {
            error_log("Calculate template-based score error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get section weights for evaluation
     */
    private function getSectionWeights(int $evaluationId): array {
        $sql = "SELECT section_type, weight_percentage FROM evaluation_section_weights WHERE evaluation_id = ?";
        $weights = fetchAll($sql, [$evaluationId]);
        
        $result = [
            'kpis' => 40.00,
            'competencies' => 25.00,
            'responsibilities' => 25.00,
            'values' => 10.00
        ];
        
        foreach ($weights as $weight) {
            $result[$weight['section_type']] = $weight['weight_percentage'];
        }
        
        return $result;
    }
    
    /**
     * Calculate KPI section score
     */
    private function calculateKPIScore(int $evaluationId): array {
        $sql = "SELECT AVG(score) as average_score, COUNT(*) as count 
                FROM evaluation_kpi_results 
                WHERE evaluation_id = ? AND score IS NOT NULL";
        $result = fetchOne($sql, [$evaluationId]);
        
        return [
            'average_score' => $result['average_score'] ?? 0,
            'count' => $result['count'] ?? 0
        ];
    }
    
    /**
     * Calculate competency section score
     */
    private function calculateCompetencyScore(int $evaluationId): array {
        $sql = "SELECT AVG(score) as average_score, COUNT(*) as count 
                FROM evaluation_competency_results 
                WHERE evaluation_id = ? AND score IS NOT NULL";
        $result = fetchOne($sql, [$evaluationId]);
        
        return [
            'average_score' => $result['average_score'] ?? 0,
            'count' => $result['count'] ?? 0
        ];
    }
    
    /**
     * Calculate responsibility section score
     */
    private function calculateResponsibilityScore(int $evaluationId): array {
        $sql = "SELECT AVG(score) as average_score, COUNT(*) as count 
                FROM evaluation_responsibility_results 
                WHERE evaluation_id = ? AND score IS NOT NULL";
        $result = fetchOne($sql, [$evaluationId]);
        
        return [
            'average_score' => $result['average_score'] ?? 0,
            'count' => $result['count'] ?? 0
        ];
    }
    
    /**
     * Calculate value section score
     */
    private function calculateValueScore(int $evaluationId): array {
        $sql = "SELECT AVG(score) as average_score, COUNT(*) as count 
                FROM evaluation_value_results 
                WHERE evaluation_id = ? AND score IS NOT NULL";
        $result = fetchOne($sql, [$evaluationId]);
        
        return [
            'average_score' => $result['average_score'] ?? 0,
            'count' => $result['count'] ?? 0
        ];
    }
    
    /**
     * Update overall rating
     */
    private function updateOverallRating(int $evaluationId): void {
        $overallRating = $this->calculateTemplateBasedScore($evaluationId);
        
        // Set overall rating to the calculated score, or NULL if no scores are entered yet
        $ratingValue = ($overallRating > 0) ? $overallRating : null;
        
        $sql = "UPDATE evaluations SET overall_rating = ?, updated_at = NOW() WHERE evaluation_id = ?";
        updateRecord($sql, [$ratingValue, $evaluationId]);
    }
    
    /**
     * Get job template evaluation data
     * @param int $evaluationId
     * @return array|false
     */
    public function getJobTemplateEvaluation(int $evaluationId) {
        $evaluation = $this->getEvaluationById($evaluationId);
        if (!$evaluation) {
            return false;
        }
        
        // LOG: Debug evaluation basic info
        error_log("GET_JOB_TEMPLATE_EVALUATION - Evaluation ID: $evaluationId");
        error_log("GET_JOB_TEMPLATE_EVALUATION - Job Template ID: " . ($evaluation['job_template_id'] ?? 'NULL'));
        error_log("GET_JOB_TEMPLATE_EVALUATION - Employee ID: " . ($evaluation['employee_id'] ?? 'NULL'));
        
        // Get KPI results
        $evaluation['kpi_results'] = $this->getKPIResults($evaluationId);
        error_log("GET_JOB_TEMPLATE_EVALUATION - KPI Results: " . count($evaluation['kpi_results']));
        
        // Get competency results
        $evaluation['competency_results'] = $this->getCompetencyResults($evaluationId);
        error_log("GET_JOB_TEMPLATE_EVALUATION - Competency Results: " . count($evaluation['competency_results']));
        
        // Get responsibility results
        $evaluation['responsibility_results'] = $this->getResponsibilityResults($evaluationId);
        error_log("GET_JOB_TEMPLATE_EVALUATION - Responsibility Results: " . count($evaluation['responsibility_results']));
        
        // Get value results
        $evaluation['value_results'] = $this->getValueResults($evaluationId);
        error_log("GET_JOB_TEMPLATE_EVALUATION - Value Results: " . count($evaluation['value_results']));
        
        // Get section weights
        $evaluation['section_weights'] = $this->getSectionWeights($evaluationId);
        
        return $evaluation;
    }
    
    /**
     * Get KPI results for evaluation
     */
    private function getKPIResults(int $evaluationId): array {
        $sql = "SELECT ekr.*, ck.kpi_name, ck.measurement_unit, ck.category
                FROM evaluation_kpi_results ekr
                JOIN company_kpis ck ON ekr.kpi_id = ck.id
                WHERE ekr.evaluation_id = ?
                ORDER BY ck.category, ck.kpi_name";
        
        $results = fetchAll($sql, [$evaluationId]);
        error_log("GET_KPI_RESULTS - Evaluation ID: $evaluationId, Results: " . count($results));
        if (empty($results)) {
            error_log("GET_KPI_RESULTS - WARNING: No KPI results found for evaluation $evaluationId");
            // Check if evaluation_kpi_results table has any records for this evaluation
            $checkSql = "SELECT COUNT(*) as count FROM evaluation_kpi_results WHERE evaluation_id = ?";
            $checkResult = fetchOne($checkSql, [$evaluationId]);
            error_log("GET_KPI_RESULTS - Raw KPI results count in table: " . ($checkResult['count'] ?? 0));
        }
        return $results;
    }
    
    /**
     * Get competency results for evaluation
     */
    private function getCompetencyResults(int $evaluationId): array {
        $sql = "SELECT ecr.*, c.competency_name, c.competency_type, cc.category_name
                FROM evaluation_competency_results ecr
                JOIN competencies c ON ecr.competency_id = c.id
                LEFT JOIN competency_categories cc ON c.category_id = cc.id
                WHERE ecr.evaluation_id = ?
                ORDER BY cc.category_name, c.competency_name";
        
        $results = fetchAll($sql, [$evaluationId]);
        error_log("GET_COMPETENCY_RESULTS - Evaluation ID: $evaluationId, Results: " . count($results));
        if (empty($results)) {
            error_log("GET_COMPETENCY_RESULTS - WARNING: No competency results found for evaluation $evaluationId");
            $checkSql = "SELECT COUNT(*) as count FROM evaluation_competency_results WHERE evaluation_id = ?";
            $checkResult = fetchOne($checkSql, [$evaluationId]);
            error_log("GET_COMPETENCY_RESULTS - Raw competency results count in table: " . ($checkResult['count'] ?? 0));
        }
        return $results;
    }
    
    /**
     * Get responsibility results for evaluation
     */
    private function getResponsibilityResults(int $evaluationId): array {
        $sql = "SELECT err.*, jtr.responsibility_text, jtr.sort_order
                FROM evaluation_responsibility_results err
                JOIN job_template_responsibilities jtr ON err.responsibility_id = jtr.id
                WHERE err.evaluation_id = ?
                ORDER BY jtr.sort_order";
        
        $results = fetchAll($sql, [$evaluationId]);
        error_log("GET_RESPONSIBILITY_RESULTS - Evaluation ID: $evaluationId, Results: " . count($results));
        if (empty($results)) {
            error_log("GET_RESPONSIBILITY_RESULTS - WARNING: No responsibility results found for evaluation $evaluationId");
            $checkSql = "SELECT COUNT(*) as count FROM evaluation_responsibility_results WHERE evaluation_id = ?";
            $checkResult = fetchOne($checkSql, [$evaluationId]);
            error_log("GET_RESPONSIBILITY_RESULTS - Raw responsibility results count in table: " . ($checkResult['count'] ?? 0));
        }
        return $results;
    }
    
    /**
     * Get value results for evaluation
     */
    private function getValueResults(int $evaluationId): array {
        $sql = "SELECT evr.*, cv.value_name, cv.description
                FROM evaluation_value_results evr
                JOIN company_values cv ON evr.value_id = cv.id
                WHERE evr.evaluation_id = ?
                ORDER BY cv.sort_order, cv.value_name";
        
        $results = fetchAll($sql, [$evaluationId]);
        error_log("GET_VALUE_RESULTS - Evaluation ID: $evaluationId, Results: " . count($results));
        if (empty($results)) {
            error_log("GET_VALUE_RESULTS - WARNING: No value results found for evaluation $evaluationId");
            $checkSql = "SELECT COUNT(*) as count FROM evaluation_value_results WHERE evaluation_id = ?";
            $checkResult = fetchOne($checkSql, [$evaluationId]);
            error_log("GET_VALUE_RESULTS - Raw value results count in table: " . ($checkResult['count'] ?? 0));
        }
        return $results;
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
                return true; // Nothing to update
            }
            
            // Update overall rating
            $this->updateOverallRating($evaluationId);
            
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
                       p.period_name, p.start_date, p.end_date,
                       jpt.position_title as job_template_title
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                LEFT JOIN job_position_templates jpt ON e.job_template_id = jpt.id
                WHERE e.evaluation_id = ?";
        
        $evaluation = fetchOne($sql, [$evaluationId]);
        
        // LOG: Debug evaluation basic data
        if ($evaluation) {
            error_log("GET_EVALUATION_BY_ID - ID: $evaluationId, Job Template ID: " . ($evaluation['job_template_id'] ?? 'NULL'));
            error_log("GET_EVALUATION_BY_ID - Employee ID: " . ($evaluation['employee_id'] ?? 'NULL'));
            error_log("GET_EVALUATION_BY_ID - Created: " . ($evaluation['created_at'] ?? 'NULL'));
        } else {
            error_log("GET_EVALUATION_BY_ID - Evaluation not found: $evaluationId");
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
                       p.period_name, p.start_date, p.end_date,
                       jpt.position_title as job_template_title
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                LEFT JOIN job_position_templates jpt ON e.job_template_id = jpt.id
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
     * Get evaluation template structure for display
     * @return array
     */
    public function getEvaluationTemplate() {
        return [
            'expected_results' => ['title' => 'Expected Results'],
            'skills_competencies' => ['title' => 'Skills & Competencies'],
            'key_responsibilities' => ['title' => 'Key Responsibilities'],
            'living_values' => ['title' => 'Living Values']
        ];
    }
}
?>