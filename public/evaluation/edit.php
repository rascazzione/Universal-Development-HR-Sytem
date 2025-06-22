<?php
/**
 * Evaluation Edit Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require authentication
requireAuth();

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? null;
if (!$evaluationId) {
    redirect('/evaluation/list.php');
}

// Initialize classes
$evaluationClass = new Evaluation();
$employeeClass = new Employee();
$periodClass = new EvaluationPeriod();

// Get evaluation details
$evaluation = $evaluationClass->getEvaluationById($evaluationId);
if (!$evaluation) {
    setFlashMessage('Evaluation not found.', 'error');
    redirect('/evaluation/list.php');
}

// Check permissions
$userRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Only allow editing if user is the evaluator or HR admin, and evaluation is in draft status
if ($evaluation['status'] !== 'draft' || 
    ($userRole !== 'hr_admin' && $evaluation['evaluator_id'] != $currentUserId)) {
    setFlashMessage('You do not have permission to edit this evaluation.', 'error');
    redirect('/evaluation/view.php?id=' . $evaluationId);
}

$pageTitle = 'Edit Evaluation - ' . $evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name'];
$pageHeader = true;
$pageDescription = 'Edit performance evaluation';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [];
        
        // Expected Results section
        if (isset($_POST['expected_results'])) {
            $updateData['expected_results'] = $_POST['expected_results'];
            $updateData['expected_results_score'] = floatval($_POST['expected_results_score'] ?? 0);
            $updateData['expected_results_weight'] = floatval($_POST['expected_results_weight'] ?? 40);
        }
        
        // Skills and Competencies section
        if (isset($_POST['skills_competencies'])) {
            $updateData['skills_competencies'] = $_POST['skills_competencies'];
            $updateData['skills_competencies_score'] = floatval($_POST['skills_competencies_score'] ?? 0);
            $updateData['skills_competencies_weight'] = floatval($_POST['skills_competencies_weight'] ?? 25);
        }
        
        // Key Responsibilities section
        if (isset($_POST['key_responsibilities'])) {
            $updateData['key_responsibilities'] = $_POST['key_responsibilities'];
            $updateData['key_responsibilities_score'] = floatval($_POST['key_responsibilities_score'] ?? 0);
            $updateData['key_responsibilities_weight'] = floatval($_POST['key_responsibilities_weight'] ?? 25);
        }
        
        // Living Values section
        if (isset($_POST['living_values'])) {
            $updateData['living_values'] = $_POST['living_values'];
            $updateData['living_values_score'] = floatval($_POST['living_values_score'] ?? 0);
            $updateData['living_values_weight'] = floatval($_POST['living_values_weight'] ?? 10);
        }
        
        // Overall evaluation
        if (isset($_POST['overall_comments'])) {
            $updateData['overall_comments'] = $_POST['overall_comments'];
        }
        
        // Goals and development
        if (isset($_POST['goals_next_period'])) {
            $updateData['goals_next_period'] = $_POST['goals_next_period'];
        }
        
        if (isset($_POST['development_areas'])) {
            $updateData['development_areas'] = $_POST['development_areas'];
        }
        
        if (isset($_POST['strengths'])) {
            $updateData['strengths'] = $_POST['strengths'];
        }
        
        // Status update
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_draft':
                    $updateData['status'] = 'draft';
                    break;
                case 'submit':
                    $updateData['status'] = 'completed';
                    break;
            }
        }
        
        $result = $evaluationClass->updateEvaluation($evaluationId, $updateData);
        
        if ($result) {
            $message = $_POST['action'] === 'submit' ? 'Evaluation submitted successfully!' : 'Evaluation saved successfully!';
            setFlashMessage($message, 'success');
            redirect('/evaluation/view.php?id=' . $evaluationId);
        } else {
            setFlashMessage('Failed to update evaluation. Please try again.', 'error');
        }
    } catch (Exception $e) {
        error_log('Evaluation update error: ' . $e->getMessage());
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Get evaluation template
$template = $evaluationClass->getEvaluationTemplate();

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Edit Performance Evaluation</h5>
                        <div class="text-muted">
                            <strong>Employee:</strong> <?php echo htmlspecialchars($evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name']); ?> |
                            <strong>Period:</strong> <?php echo htmlspecialchars($evaluation['period_name']); ?> |
                            <strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($evaluation['status']); ?></span>
                        </div>
                    </div>
                    <div>
                        <a href="/evaluation/view.php?id=<?php echo $evaluationId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-eye me-2"></i>View Only
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate data-autosave="evaluation_<?php echo $evaluationId; ?>">
                    
                    <!-- Expected Results Section -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">
                            <?php echo $template['expected_results']['title']; ?>
                            <span class="badge bg-primary ms-2"><?php echo $template['expected_results']['weight']; ?>%</span>
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Comments and Feedback</label>
                                    <textarea class="form-control" name="expected_results[comments]" rows="4" 
                                              placeholder="Provide detailed feedback on expected results..."><?php echo htmlspecialchars($evaluation['expected_results']['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <input type="number" class="form-control" name="expected_results_score" 
                                           min="1" max="5" step="0.1" 
                                           value="<?php echo $evaluation['expected_results_score'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" name="expected_results_weight" 
                                           min="0" max="100" 
                                           value="<?php echo $evaluation['expected_results_weight'] ?? 40; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Skills and Competencies Section -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">
                            <?php echo $template['skills_competencies']['title']; ?>
                            <span class="badge bg-primary ms-2"><?php echo $template['skills_competencies']['weight']; ?>%</span>
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Comments and Feedback</label>
                                    <textarea class="form-control" name="skills_competencies[comments]" rows="4" 
                                              placeholder="Provide detailed feedback on skills and competencies..."><?php echo htmlspecialchars($evaluation['skills_competencies']['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <input type="number" class="form-control" name="skills_competencies_score" 
                                           min="1" max="5" step="0.1" 
                                           value="<?php echo $evaluation['skills_competencies_score'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" name="skills_competencies_weight" 
                                           min="0" max="100" 
                                           value="<?php echo $evaluation['skills_competencies_weight'] ?? 25; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Responsibilities Section -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">
                            <?php echo $template['key_responsibilities']['title']; ?>
                            <span class="badge bg-primary ms-2"><?php echo $template['key_responsibilities']['weight']; ?>%</span>
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Comments and Feedback</label>
                                    <textarea class="form-control" name="key_responsibilities[comments]" rows="4" 
                                              placeholder="Provide detailed feedback on key responsibilities..."><?php echo htmlspecialchars($evaluation['key_responsibilities']['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <input type="number" class="form-control" name="key_responsibilities_score" 
                                           min="1" max="5" step="0.1" 
                                           value="<?php echo $evaluation['key_responsibilities_score'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" name="key_responsibilities_weight" 
                                           min="0" max="100" 
                                           value="<?php echo $evaluation['key_responsibilities_weight'] ?? 25; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Living Values Section -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">
                            <?php echo $template['living_values']['title']; ?>
                            <span class="badge bg-primary ms-2"><?php echo $template['living_values']['weight']; ?>%</span>
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Comments and Feedback</label>
                                    <textarea class="form-control" name="living_values[comments]" rows="4" 
                                              placeholder="Provide detailed feedback on living our values..."><?php echo htmlspecialchars($evaluation['living_values']['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <input type="number" class="form-control" name="living_values_score" 
                                           min="1" max="5" step="0.1" 
                                           value="<?php echo $evaluation['living_values_score'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control" name="living_values_weight" 
                                           min="0" max="100" 
                                           value="<?php echo $evaluation['living_values_weight'] ?? 10; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Comments -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">Overall Comments</h6>
                        <div class="mb-3">
                            <label class="form-label">Overall Performance Summary</label>
                            <textarea class="form-control" name="overall_comments" rows="4" 
                                      placeholder="Provide an overall summary of performance..."><?php echo htmlspecialchars($evaluation['overall_comments'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Development and Goals -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">Development and Goals</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Strengths</label>
                                    <textarea class="form-control" name="strengths" rows="4" 
                                              placeholder="List key strengths and achievements..."><?php echo htmlspecialchars($evaluation['strengths'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Development Areas</label>
                                    <textarea class="form-control" name="development_areas" rows="4" 
                                              placeholder="Identify areas for improvement..."><?php echo htmlspecialchars($evaluation['development_areas'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Goals for Next Period</label>
                            <textarea class="form-control" name="goals_next_period" rows="4" 
                                      placeholder="Set goals and objectives for the next evaluation period..."><?php echo htmlspecialchars($evaluation['goals_next_period'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Auto-save indicator -->
                    <div class="mb-3">
                        <span id="autosave-indicator"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="/evaluation/list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <div>
                            <button type="submit" name="action" value="save_draft" class="btn btn-outline-primary me-2">
                                <i class="fas fa-save me-2"></i>Save Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Submit Evaluation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.evaluation-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-title {
    color: #495057;
    font-weight: 600;
}
</style>

<?php include __DIR__ . '/../../templates/footer.php'; ?>