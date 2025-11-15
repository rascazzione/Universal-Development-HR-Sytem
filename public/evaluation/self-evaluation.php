<?php
/**
 * Self-Evaluation Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';

// Require authentication
requireAuth();

$pageTitle = 'Self-Evaluation';
$pageHeader = true;
$pageDescription = 'Complete your self-assessment for the current evaluation period';

// Initialize classes
$evaluationClass = new Evaluation();
$periodClass = new EvaluationPeriod();
$employeeClass = new Employee();
$jobTemplateClass = new JobTemplate();

// Get current employee
$employeeId = $_SESSION['employee_id'];
$userId = $_SESSION['user_id'];

if (!$employeeId) {
    setFlashMessage('Employee profile not found. Please contact HR.', 'error');
    redirect('/dashboard.php');
}

// Get active evaluation period for employee
$activePeriod = $periodClass->getActivePeriodForEmployee($employeeId);
if (!$activePeriod) {
    setFlashMessage('No active evaluation period found. Please contact HR.', 'warning');
    redirect('/evaluation/list.php');
}

// Get or create self-evaluation for this period
$selfEvaluation = $evaluationClass->getSelfEvaluationForPeriod($employeeId, $activePeriod['period_id']);
if (!$selfEvaluation) {
    // Create self-evaluation if it doesn't exist
    $evaluationData = [
        'employee_id' => $employeeId,
        'evaluator_id' => $userId,
        'period_id' => $activePeriod['period_id'],
        'evaluation_type' => 'self',
        'workflow_state' => 'pending_self'
    ];
    
    $selfEvaluationId = $evaluationClass->createEvaluation($evaluationData);
    if ($selfEvaluationId) {
        $selfEvaluation = $evaluationClass->getEvaluationById($selfEvaluationId);
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    protect_csrf();
    
    try {
        // Validate required fields
        $requiredFields = ['expected_results_score', 'skills_competencies_score', 
                          'key_responsibilities_score', 'living_values_score', 'overall_comments'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Please complete all required sections before submitting.";
                break;
            }
        }
        
        if (empty($errors)) {
            // Update self-evaluation
            $updateData = [
                'expected_results_score' => (float)$_POST['expected_results_score'],
                'skills_competencies_score' => (float)$_POST['skills_competencies_score'],
                'key_responsibilities_score' => (float)$_POST['key_responsibilities_score'],
                'living_values_score' => (float)$_POST['living_values_score'],
                'overall_comments' => $_POST['overall_comments'],
                'overall_rating' => ((float)$_POST['expected_results_score'] + 
                                   (float)$_POST['skills_competencies_score'] + 
                                   (float)$_POST['key_responsibilities_score'] + 
                                   (float)$_POST['living_values_score']) / 4
            ];
            
            $success = $evaluationClass->updateEvaluation($selfEvaluation['evaluation_id'], $updateData);
            
            if ($success) {
                // Advance workflow state
                $workflowClass = new EvaluationWorkflow();
                $workflowSuccess = $workflowClass->submitSelfEvaluation($selfEvaluation['evaluation_id']);
                
                if ($workflowSuccess) {
                    setFlashMessage('Self-evaluation submitted successfully!', 'success');
                    redirect('/evaluation/list.php');
                } else {
                    $errors[] = 'Error submitting self-evaluation. Please try again.';
                }
            } else {
                $errors[] = 'Error updating self-evaluation. Please try again.';
            }
        }
    } catch (Exception $e) {
        error_log("Self-evaluation submission error: " . $e->getMessage());
        $errors[] = 'An error occurred while submitting your self-evaluation.';
    }
}

// Get job template data for context
$jobTemplateData = [];
if ($selfEvaluation && !empty($selfEvaluation['job_template_id'])) {
    $jobTemplateData = $jobTemplateClass->getCompleteJobTemplate($selfEvaluation['job_template_id']);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-check me-2"></i>Self-Evaluation
                </h5>
                <small class="text-muted">
                    Period: <?php echo htmlspecialchars($activePeriod['period_name']); ?> 
                    (<?php echo formatDate($activePeriod['start_date']); ?> - <?php echo formatDate($activePeriod['end_date']); ?>)
                </small>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Self-evaluation submitted successfully! Your manager will be notified.
                </div>
                <?php endif; ?>
                
                <?php if (!$success && empty($errors)): ?>
                <form method="POST" id="selfEvaluationForm">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <!-- Job Template Context -->
                    <?php if (!empty($jobTemplateData)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-briefcase me-2"></i>Your Role & Responsibilities
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Position</h6>
                                            <p><?php echo htmlspecialchars($jobTemplateData['template']['template_name'] ?? 'Not assigned'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Department</h6>
                                            <p><?php echo htmlspecialchars($jobTemplateData['template']['department'] ?? 'Not assigned'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Expected Results Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-target me-2"></i>Expected Results & KPIs
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="expected_results_score" class="form-label">Score (1-5)</label>
                                        <input type="number" class="form-control" id="expected_results_score" 
                                               name="expected_results_score" min="1" max="5" step="0.1" required
                                               value="<?php echo htmlspecialchars($selfEvaluation['expected_results_score'] ?? ''); ?>">
                                        <div class="form-text">Rate your achievement of expected results and KPIs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills & Competencies Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>Skills & Competencies
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="skills_competencies_score" class="form-label">Score (1-5)</label>
                                        <input type="number" class="form-control" id="skills_competencies_score" 
                                               name="skills_competencies_score" min="1" max="5" step="0.1" required
                                               value="<?php echo htmlspecialchars($selfEvaluation['skills_competencies_score'] ?? ''); ?>">
                                        <div class="form-text">Rate your skills, knowledge, and competencies</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Key Responsibilities Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-tasks me-2"></i>Key Responsibilities
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="key_responsibilities_score" class="form-label">Score (1-5)</label>
                                        <input type="number" class="form-control" id="key_responsibilities_score" 
                                               name="key_responsibilities_score" min="1" max="5" step="0.1" required
                                               value="<?php echo htmlspecialchars($selfEvaluation['key_responsibilities_score'] ?? ''); ?>">
                                        <div class="form-text">Rate your performance in key responsibilities</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Living Our Values Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-heart me-2"></i>Living Our Values
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="living_values_score" class="form-label">Score (1-5)</label>
                                        <input type="number" class="form-control" id="living_values_score" 
                                               name="living_values_score" min="1" max="5" step="0.1" required
                                               value="<?php echo htmlspecialchars($selfEvaluation['living_values_score'] ?? ''); ?>">
                                        <div class="form-text">Rate how you demonstrate company values</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overall Comments Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-comment me-2"></i>Overall Comments
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="overall_comments" class="form-label">Self-Reflection Comments</label>
                                        <textarea class="form-control" id="overall_comments" 
                                                  name="overall_comments" rows="6" required
                                                  placeholder="Provide your self-reflection on your performance this period..."><?php echo htmlspecialchars($selfEvaluation['overall_comments'] ?? ''); ?></textarea>
                                        <div class="form-text">Share your achievements, challenges, and areas for development</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="/evaluation/list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Self-Evaluation
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('selfEvaluationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate all scores are filled
            const scores = ['expected_results_score', 'skills_competencies_score', 
                          'key_responsibilities_score', 'living_values_score'];
            
            let isValid = true;
            for (const scoreField of scores) {
                const field = document.getElementById(scoreField);
                if (field && (!field.value || field.value < 1 || field.value > 5)) {
                    isValid = false;
                    break;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please provide valid scores (1-5) for all sections before submitting.');
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>