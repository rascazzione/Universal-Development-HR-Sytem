<?php
/**
 * Manager Review Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/EvaluationWorkflow.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';

// Require manager or HR admin role
requireRole(['manager', 'hr_admin']);

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? null;
if (!$evaluationId) {
    setFlashMessage('Evaluation ID is required.', 'error');
    redirect('/evaluation/list.php');
}

// Initialize classes
$evaluationClass = new Evaluation();
$workflowClass = new EvaluationWorkflow();
$employeeClass = new Employee();
$jobTemplateClass = new JobTemplate();

// Get self-evaluation
$selfEvaluation = $evaluationClass->getEvaluationById($evaluationId);
if (!$selfEvaluation || $selfEvaluation['evaluation_type'] !== 'self') {
    setFlashMessage('Invalid self-evaluation reference.', 'error');
    redirect('/evaluation/list.php');
}

// Check permissions
$currentUserEmployeeId = $_SESSION['employee_id'];
$userRole = $_SESSION['user_role'];
$canReview = ($userRole === 'hr_admin') || 
              ($userRole === 'manager' && $selfEvaluation['manager_id'] == $currentUserEmployeeId);

if (!$canReview) {
    setFlashMessage('You do not have permission to review this evaluation.', 'error');
    redirect('/evaluation/list.php');
}

// Get employee details
$employee = $employeeClass->getEmployeeById($selfEvaluation['employee_id']);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/evaluation/list.php');
}

// Check if manager evaluation already exists
$managerEvaluation = $evaluationClass->getManagerEvaluationForSelfEvaluation($evaluationId);
$creatingNew = !$managerEvaluation;

if (!$managerEvaluation) {
    // Create manager evaluation
    try {
        $managerEvaluationId = $workflowClass->createManagerEvaluation($evaluationId);
        if ($managerEvaluationId) {
            $managerEvaluation = $evaluationClass->getEvaluationById($managerEvaluationId);
        }
    } catch (Exception $e) {
        setFlashMessage('Error creating manager evaluation: ' . $e->getMessage(), 'error');
        redirect('/evaluation/list.php');
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
            // Update manager evaluation
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
            
            $success = $evaluationClass->updateEvaluation($managerEvaluation['evaluation_id'], $updateData);
            
            if ($success) {
                // Submit manager evaluation and generate final
                $workflowSuccess = $workflowClass->submitManagerEvaluation($managerEvaluation['evaluation_id']);
                
                if ($workflowSuccess) {
                    setFlashMessage('Manager evaluation submitted successfully! Final evaluation generated.', 'success');
                    redirect('/evaluation/list.php');
                } else {
                    $errors[] = 'Error submitting manager evaluation. Please try again.';
                }
            } else {
                $errors[] = 'Error updating manager evaluation. Please try again.';
            }
        }
    } catch (Exception $e) {
        error_log("Manager evaluation submission error: " . $e->getMessage());
        $errors[] = 'An error occurred while submitting your evaluation.';
    }
}

// Get job template data for context
$jobTemplateData = [];
if ($employee && !empty($employee['job_template_id'])) {
    $jobTemplateData = $jobTemplateClass->getCompleteJobTemplate($employee['job_template_id']);
}

$pageTitle = $creatingNew ? 'Create Manager Evaluation' : 'Review Self-Evaluation';
$pageHeader = true;
$pageDescription = $creatingNew ? 'Complete your evaluation for ' . htmlspecialchars($employee['first_name'] . ' ' . htmlspecialchars($employee['last_name']) : 'Review self-evaluation for ' . htmlspecialchars($employee['first_name'] . ' ' . htmlspecialchars($employee['last_name']);

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-tie me-2"></i><?php echo $pageTitle; ?>
                </h5>
                <small class="text-muted">
                    Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?> 
                    | Period: <?php echo htmlspecialchars($selfEvaluation['period_name']); ?>
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
                    Manager evaluation submitted successfully! Final evaluation has been generated.
                </div>
                <?php endif; ?>
                
                <?php if (!$success && empty($errors)): ?>
                
                <!-- Comparison View -->
                <div class="row mb-4">
                    <!-- Self-Evaluation (Read-only) -->
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Employee Self-Evaluation
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Expected Results Score</label>
                                            <div class="form-control-plaintext">
                                                <?php echo number_format($selfEvaluation['expected_results_score'], 1); ?>/5.0
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $selfEvaluation['expected_results_score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Skills Score</label>
                                            <div class="form-control-plaintext">
                                                <?php echo number_format($selfEvaluation['skills_competencies_score'], 1); ?>/5.0
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $selfEvaluation['skills_competencies_score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <label class="form-label">Responsibilities Score</label>
                                                <div class="form-control-plaintext">
                                                    <?php echo number_format($selfEvaluation['key_responsibilities_score'], 1); ?>/5.0
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $selfEvaluation['key_responsibilities_score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Values Score</label>
                                                <div class="form-control-plaintext">
                                                    <?php echo number_format($selfEvaluation['living_values_score'], 1); ?>/5.0
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $selfEvaluation['living_values_score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Self-Reflection Comments</label>
                                            <div class="form-control-plaintext" style="min-height: 80px;">
                                                <?php echo htmlspecialchars($selfEvaluation['overall_comments'] ?? 'No comments provided'); ?>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Manager Evaluation Form -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-tie me-2"></i>Your Manager Evaluation
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="managerEvaluationForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        
                                        <!-- Expected Results Section -->
                                        <div class="mb-3">
                                            <label for="expected_results_score" class="form-label">Score (1-5)</label>
                                            <input type="number" class="form-control" id="expected_results_score" 
                                                   name="expected_results_score" min="1" max="5" step="0.1" required
                                                   value="<?php echo htmlspecialchars($managerEvaluation['expected_results_score'] ?? ''); ?>">
                                            <div class="form-text">Rate employee's achievement of expected results and KPIs</div>
                                        </div>
                                        
                                        <!-- Skills & Competencies Section -->
                                        <div class="mb-3">
                                            <label for="skills_competencies_score" class="form-label">Score (1-5)</label>
                                            <input type="number" class="form-control" id="skills_competencies_score" 
                                                   name="skills_competencies_score" min="1" max="5" step="0.1" required
                                                   value="<?php echo htmlspecialchars($managerEvaluation['skills_competencies_score'] ?? ''); ?>">
                                            <div class="form-text">Rate employee's skills, knowledge, and competencies</div>
                                        </div>
                                        
                                        <!-- Key Responsibilities Section -->
                                        <div class="mb-3">
                                            <label for="key_responsibilities_score" class="form-label">Score (1-5)</label>
                                            <input type="number" class="form-control" id="key_responsibilities_score" 
                                                   name="key_responsibilities_score" min="1" max="5" step="0.1" required
                                                   value="<?php echo htmlspecialchars($managerEvaluation['key_responsibilities_score'] ?? ''); ?>">
                                            <div class="form-text">Rate employee's performance in key responsibilities</div>
                                        </div>
                                        
                                        <!-- Living Our Values Section -->
                                        <div class="mb-3">
                                            <label for="living_values_score" class="form-label">Score (1-5)</label>
                                            <input type="number" class="form-control" id="living_values_score" 
                                                   name="living_values_score" min="1" max="5" step="0.1" required
                                                   value="<?php echo htmlspecialchars($managerEvaluation['living_values_score'] ?? ''); ?>">
                                            <div class="form-text">Rate how employee demonstrates company values</div>
                                        </div>
                                        
                                        <!-- Overall Comments Section -->
                                        <div class="mb-3">
                                            <label for="overall_comments" class="form-label">Manager Comments</label>
                                            <textarea class="form-control" id="overall_comments" 
                                                      name="overall_comments" rows="6" required
                                                      placeholder="Provide your evaluation comments..."><?php echo htmlspecialchars($managerEvaluation['overall_comments'] ?? ''); ?></textarea>
                                            <div class="form-text">Share your assessment of the employee's performance</div>
                                        </div>
                                        
                                        <!-- Submit Button -->
                                        <div class="d-flex justify-content-between mt-4">
                                            <a href="/evaluation/list.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Submit Evaluation
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Evidence/Feedback History -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Feedback & Evidence
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Review recent feedback and evidence to inform your evaluation:
                                    </div>
                                    
                                    <a href="/employees/view-feedback.php?employee_id=<?php echo $selfEvaluation['employee_id']; ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye me-2"></i>View Full Feedback History
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('managerEvaluationForm');
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