<?php
/**
 * Evaluation Workflow Management Class
 * Handles the structured evaluation process: Self -> Manager -> Final
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Evaluation.php';
require_once __DIR__ . '/EvaluationPeriod.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';

class EvaluationWorkflow {
    private $pdo;
    private $evaluationClass;
    private $periodClass;
    private $employeeClass;
    private $notificationManager;
    
    // Valid workflow transitions
    private $validTransitions = [
        'pending_self' => ['self_submitted'],
        'self_submitted' => ['pending_manager'],
        'pending_manager' => ['manager_submitted'],
        'manager_submitted' => ['final_delivered']
    ];
    
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->evaluationClass = new Evaluation();
        $this->periodClass = new EvaluationPeriod();
        $this->employeeClass = new Employee();
        $this->notificationManager = new NotificationManager();
    }
    
    /**
     * Initialize evaluation cycle for all active employees when period is activated
     * @param int $periodId
     * @return bool
     */
    public function initializeEvaluationCycle($periodId) {
        try {
            // Get active employees
            $activeEmployees = $this->employeeClass->getActiveEmployees();
            
            $createdCount = 0;
            foreach ($activeEmployees as $employee) {
                // Check if self-evaluation already exists
                if (!$this->hasSelfEvaluation($employee['employee_id'], $periodId)) {
                    $evaluationData = [
                        'employee_id' => $employee['employee_id'],
                        'evaluator_id' => $employee['user_id'], // Self-evaluation
                        'period_id' => $periodId,
                        'evaluation_type' => 'self',
                        'workflow_state' => 'pending_self'
                    ];
                    
                    $evaluationId = $this->evaluationClass->createEvaluation($evaluationData);
                    if ($evaluationId) {
                        $createdCount++;
                        $this->notificationManager->notifyEvaluationPeriodStarted($employee['user_id'], $periodId, $evaluationId);
                    }
                }
            }
            
            error_log("Evaluation cycle initialized: Created $createdCount self-evaluations for period $periodId");
            return true;
        } catch (Exception $e) {
            error_log("Error initializing evaluation cycle: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if employee has self-evaluation for period
     * @param int $employeeId
     * @param int $periodId
     * @return bool
     */
    private function hasSelfEvaluation($employeeId, $periodId) {
        $sql = "SELECT COUNT(*) as count FROM evaluations 
                WHERE employee_id = ? AND period_id = ? AND evaluation_type = 'self'";
        $result = fetchOne($sql, [$employeeId, $periodId]);
        return $result['count'] > 0;
    }
    
    /**
     * Submit self-evaluation
     * @param int $evaluationId
     * @return bool
     */
    public function submitSelfEvaluation($evaluationId) {
        try {
            // Validate evaluation completeness
            if (!$this->validateEvaluationCompleteness($evaluationId, 'self')) {
                throw new Exception("Self-evaluation is incomplete. Please fill all required sections.");
            }
            
            // Update workflow state
            $success = $this->advanceWorkflowState($evaluationId, 'self_submitted', $_SESSION['user_id']);
            
            if ($success) {
                // Get evaluation details for notification
                $evaluation = $this->evaluationClass->getEvaluationById($evaluationId);
                $this->notificationManager->notifySelfEvaluationSubmitted($evaluation['employee_id'], $evaluationId);
                
                // Notify manager
                if ($evaluation['manager_id']) {
                    $manager = $this->employeeClass->getEmployeeById($evaluation['manager_id']);
                    if ($manager && $manager['user_id']) {
                        $this->notificationManager->notifyManagerOfSelfEvaluation($manager['user_id'], $evaluationId);
                    }
                }
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error submitting self-evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create manager evaluation based on self-evaluation
     * @param int $selfEvaluationId
     * @return int|false
     */
    public function createManagerEvaluation($selfEvaluationId) {
        try {
            // Get self-evaluation details
            $selfEvaluation = $this->evaluationClass->getEvaluationById($selfEvaluationId);
            if (!$selfEvaluation || $selfEvaluation['evaluation_type'] !== 'self') {
                throw new Exception("Invalid self-evaluation reference");
            }
            
            // Check if manager evaluation already exists
            if ($this->hasManagerEvaluation($selfEvaluation['employee_id'], $selfEvaluation['period_id'])) {
                throw new Exception("Manager evaluation already exists for this period");
            }
            
            // Create manager evaluation
            $evaluationData = [
                'employee_id' => $selfEvaluation['employee_id'],
                'evaluator_id' => $_SESSION['user_id'],
                'manager_id' => $_SESSION['employee_id'],
                'period_id' => $selfEvaluation['period_id'],
                'evaluation_type' => 'manager',
                'self_evaluation_id' => $selfEvaluationId,
                'workflow_state' => 'pending_manager'
            ];
            
            $managerEvaluationId = $this->evaluationClass->createEvaluation($evaluationData);
            
            if ($managerEvaluationId) {
                // Log workflow transition
                $this->logWorkflowTransition($managerEvaluationId, null, 'pending_manager', $_SESSION['user_id']);
                
                // Notify manager of evaluation assignment
                $this->notificationManager->notifyManagerEvaluationDue($_SESSION['user_id'], $managerEvaluationId);
                
                return $managerEvaluationId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating manager evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if manager evaluation exists for employee/period
     * @param int $employeeId
     * @param int $periodId
     * @return bool
     */
    private function hasManagerEvaluation($employeeId, $periodId) {
        $sql = "SELECT COUNT(*) as count FROM evaluations 
                WHERE employee_id = ? AND period_id = ? AND evaluation_type = 'manager'";
        $result = fetchOne($sql, [$employeeId, $periodId]);
        return $result['count'] > 0;
    }
    
    /**
     * Submit manager evaluation and generate final evaluation
     * @param int $managerEvaluationId
     * @return int|false
     */
    public function submitManagerEvaluation($managerEvaluationId) {
        try {
            // Validate manager evaluation completeness
            if (!$this->validateEvaluationCompleteness($managerEvaluationId, 'manager')) {
                throw new Exception("Manager evaluation is incomplete. Please fill all required sections.");
            }
            
            // Update workflow state
            $success = $this->advanceWorkflowState($managerEvaluationId, 'manager_submitted', $_SESSION['user_id']);
            
            if ($success) {
                // Get evaluation details
                $managerEvaluation = $this->evaluationClass->getEvaluationById($managerEvaluationId);
                $selfEvaluationId = $managerEvaluation['self_evaluation_id'];
                
                // Generate final evaluation
                $finalEvaluationId = $this->generateFinalEvaluation($selfEvaluationId, $managerEvaluationId);
                
                if ($finalEvaluationId) {
                    // Notify employee of final evaluation
                    $this->notificationManager->notifyFinalEvaluationDelivered($managerEvaluation['employee_id'], $finalEvaluationId);
                    
                    // Notify HR of completion
                    $this->notificationManager->notifyHROfEvaluationCompletion($finalEvaluationId);
                    
                    return $finalEvaluationId;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error submitting manager evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate final evaluation combining self and manager evaluations
     * @param int $selfEvaluationId
     * @param int $managerEvaluationId
     * @return int|false
     */
    public function generateFinalEvaluation($selfEvaluationId, $managerEvaluationId) {
        try {
            // Get both evaluations
            $selfEvaluation = $this->evaluationClass->getEvaluationById($selfEvaluationId);
            $managerEvaluation = $this->evaluationClass->getEvaluationById($managerEvaluationId);
            
            if (!$selfEvaluation || !$managerEvaluation) {
                throw new Exception("Invalid evaluation references");
            }
            
            // Create final evaluation
            $evaluationData = [
                'employee_id' => $selfEvaluation['employee_id'],
                'evaluator_id' => $_SESSION['user_id'], // HR or system
                'period_id' => $selfEvaluation['period_id'],
                'evaluation_type' => 'final',
                'self_evaluation_id' => $selfEvaluationId,
                'workflow_state' => 'final_delivered'
            ];
            
            $finalEvaluationId = $this->evaluationClass->createEvaluation($evaluationData);
            
            if ($finalEvaluationId) {
                // Combine evaluation data
                $this->combineEvaluationData($finalEvaluationId, $selfEvaluation, $managerEvaluation);
                
                // Log workflow transition
                $this->logWorkflowTransition($finalEvaluationId, null, 'final_delivered', $_SESSION['user_id']);
                
                return $finalEvaluationId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error generating final evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Combine self and manager evaluation data into final evaluation
     * @param int $finalEvaluationId
     * @param array $selfEvaluation
     * @param array $managerEvaluation
     * @return bool
     */
    private function combineEvaluationData($finalEvaluationId, $selfEvaluation, $managerEvaluation) {
        try {
            // Calculate combined scores
            $overallRating = ($selfEvaluation['overall_rating'] + $managerEvaluation['overall_rating']) / 2;
            
            // Combine comments
            $combinedComments = "=== SELF-EVALUATION ===\n" . ($selfEvaluation['overall_comments'] ?? 'No comments') . 
                               "\n\n=== MANAGER EVALUATION ===\n" . ($managerEvaluation['overall_comments'] ?? 'No comments') .
                               "\n\n=== FINAL ASSESSMENT ===\nCombined rating: " . number_format($overallRating, 2);
            
            // Update final evaluation
            $updateData = [
                'overall_rating' => $overallRating,
                'overall_comments' => $combinedComments,
                'expected_results_score' => ($selfEvaluation['expected_results_score'] + $managerEvaluation['expected_results_score']) / 2,
                'skills_competencies_score' => ($selfEvaluation['skills_competencies_score'] + $managerEvaluation['skills_competencies_score']) / 2,
                'key_responsibilities_score' => ($selfEvaluation['key_responsibilities_score'] + $managerEvaluation['key_responsibilities_score']) / 2,
                'living_values_score' => ($selfEvaluation['living_values_score'] + $managerEvaluation['living_values_score']) / 2
            ];
            
            return $this->evaluationClass->updateEvaluation($finalEvaluationId, $updateData);
        } catch (Exception $e) {
            error_log("Error combining evaluation data: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate evaluation completeness based on type
     * @param int $evaluationId
     * @param string $evaluationType
     * @return bool
     */
    private function validateEvaluationCompleteness($evaluationId, $evaluationType) {
        try {
            $evaluation = $this->evaluationClass->getEvaluationById($evaluationId);
            if (!$evaluation) {
                return false;
            }
            
            // Check required sections based on evaluation type
            $requiredSections = ['expected_results', 'skills_competencies', 'key_responsibilities', 'living_values'];
            
            foreach ($requiredSections as $section) {
                $scoreField = $section . '_score';
                if (is_null($evaluation[$scoreField]) || $evaluation[$scoreField] === '') {
                    return false;
                }
            }
            
            // Check overall rating
            if (is_null($evaluation['overall_rating'])) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error validating evaluation completeness: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Advance workflow state with validation
     * @param int $evaluationId
     * @param string $newState
     * @param int $userId
     * @return bool
     */
    public function advanceWorkflowState($evaluationId, $newState, $userId) {
        try {
            // Get current evaluation
            $evaluation = $this->evaluationClass->getEvaluationById($evaluationId);
            if (!$evaluation) {
                throw new Exception("Evaluation not found");
            }
            
            $currentState = $evaluation['workflow_state'];
            
            // Validate transition
            if (!$this->isValidTransition($currentState, $newState)) {
                throw new Exception("Invalid workflow transition from $currentState to $newState");
            }
            
            // Update evaluation
            $updateData = [
                'workflow_state' => $newState
            ];
            
            // Add timestamp based on new state
            switch ($newState) {
                case 'self_submitted':
                    $updateData['self_submitted_at'] = date('Y-m-d H:i:s');
                    break;
                case 'manager_submitted':
                    $updateData['manager_submitted_at'] = date('Y-m-d H:i:s');
                    break;
                case 'final_delivered':
                    $updateData['final_delivered_at'] = date('Y-m-d H:i:s');
                    break;
            }
            
            $success = $this->evaluationClass->updateEvaluation($evaluationId, $updateData);
            
            if ($success) {
                // Log workflow transition
                $this->logWorkflowTransition($evaluationId, $currentState, $newState, $userId);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error advancing workflow state: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if workflow transition is valid
     * @param string $fromState
     * @param string $toState
     * @return bool
     */
    private function isValidTransition($fromState, $toState) {
        if (!isset($this->validTransitions[$fromState])) {
            return false;
        }
        
        return in_array($toState, $this->validTransitions[$fromState]);
    }
    
    /**
     * Log workflow state changes
     * @param int $evaluationId
     * @param string $fromState
     * @param string $toState
     * @param int $userId
     * @return bool
     */
    private function logWorkflowTransition($evaluationId, $fromState, $toState, $userId) {
        try {
            $sql = "INSERT INTO evaluation_workflow_audit 
                    (evaluation_id, from_state, to_state, changed_by, changed_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            return insertRecord($sql, [$evaluationId, $fromState, $toState, $userId]);
        } catch (Exception $e) {
            error_log("Error logging workflow transition: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get workflow status for employee and period
     * @param int $employeeId
     * @param int $periodId
     * @return array
     */
    public function getWorkflowStatus($employeeId, $periodId) {
        try {
            $sql = "SELECT 
                        e.*,
                        emp.first_name as employee_first_name,
                        emp.last_name as employee_last_name,
                        self_eval.workflow_state as self_workflow_state,
                        self_eval.submitted_at as self_submitted_at,
                        manager_eval.workflow_state as manager_workflow_state,
                        manager_eval.submitted_at as manager_submitted_at,
                        final_eval.workflow_state as final_workflow_state,
                        final_eval.final_delivered_at
                    FROM evaluations e
                    JOIN employees emp ON e.employee_id = emp.employee_id
                    LEFT JOIN evaluations self_eval ON e.employee_id = self_eval.employee_id 
                        AND e.period_id = self_eval.period_id AND self_eval.evaluation_type = 'self'
                    LEFT JOIN evaluations manager_eval ON e.employee_id = manager_eval.employee_id 
                        AND e.period_id = manager_eval.period_id AND manager_eval.evaluation_type = 'manager'
                    LEFT JOIN evaluations final_eval ON e.employee_id = final_eval.employee_id 
                        AND e.period_id = final_eval.period_id AND final_eval.evaluation_type = 'final'
                    WHERE e.employee_id = ? AND e.period_id = ?
                    ORDER BY e.evaluation_type";
            
            $result = fetchAll($sql, [$employeeId, $periodId]);
            
            if (empty($result)) {
                return [
                    'has_self' => false,
                    'has_manager' => false,
                    'has_final' => false,
                    'current_phase' => 'not_started'
                ];
            }
            
            $status = [
                'has_self' => false,
                'has_manager' => false,
                'has_final' => false,
                'current_phase' => 'not_started'
            ];
            
            foreach ($result as $eval) {
                switch ($eval['evaluation_type']) {
                    case 'self':
                        $status['has_self'] = true;
                        $status['self_state'] = $eval['workflow_state'];
                        $status['self_submitted_at'] = $eval['self_submitted_at'];
                        break;
                    case 'manager':
                        $status['has_manager'] = true;
                        $status['manager_state'] = $eval['workflow_state'];
                        $status['manager_submitted_at'] = $eval['manager_submitted_at'];
                        break;
                    case 'final':
                        $status['has_final'] = true;
                        $status['final_state'] = $eval['workflow_state'];
                        $status['final_delivered_at'] = $eval['final_delivered_at'];
                        break;
                }
            }
            
            // Determine current phase
            if ($status['has_final']) {
                $status['current_phase'] = 'completed';
            } elseif ($status['has_manager']) {
                $status['current_phase'] = 'manager_review';
            } elseif ($status['has_self']) {
                $status['current_phase'] = 'self_evaluation';
            }
            
            return $status;
        } catch (Exception $e) {
            error_log("Error getting workflow status: " . $e->getMessage());
            return [];
        }
    }
}