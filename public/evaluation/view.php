<?php
/**
 * Evaluation View Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require authentication
requireAuth();

// Handle POST requests (delete action)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DELETE_EVALUATION_POST_REQUEST - Action: " . ($_POST['action'] ?? 'none'));
    error_log("DELETE_EVALUATION_POST_REQUEST - Evaluation ID: " . ($_POST['evaluation_id'] ?? 'none'));
    error_log("DELETE_EVALUATION_POST_REQUEST - User ID: " . ($_SESSION['user_id'] ?? 'none'));
    error_log("DELETE_EVALUATION_POST_REQUEST - User Role: " . ($_SESSION['user_role'] ?? 'none'));
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Verify CSRF token if present
        if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])) {
            if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                error_log("DELETE_EVALUATION_ERROR - CSRF token mismatch");
                setFlashMessage('Invalid request. Please try again.', 'error');
                redirect('/evaluation/view.php?id=' . ($_POST['evaluation_id'] ?? ''));
            }
        }
        
        $deleteEvaluationId = $_POST['evaluation_id'] ?? null;
        if (!$deleteEvaluationId) {
            error_log("DELETE_EVALUATION_ERROR - No evaluation ID provided");
            setFlashMessage('Invalid evaluation ID.', 'error');
            redirect('/evaluation/list.php');
        }
        
        // Check permissions - only HR admin can delete
        if ($_SESSION['user_role'] !== 'hr_admin') {
            error_log("DELETE_EVALUATION_ERROR - Insufficient permissions for user role: " . $_SESSION['user_role']);
            setFlashMessage('You do not have permission to delete evaluations.', 'error');
            redirect('/evaluation/view.php?id=' . $deleteEvaluationId);
        }
        
        try {
            $evaluationClass = new Evaluation();
            
            // Get evaluation details for logging before deletion
            $evaluationToDelete = $evaluationClass->getEvaluationById($deleteEvaluationId);
            if (!$evaluationToDelete) {
                error_log("DELETE_EVALUATION_ERROR - Evaluation not found: $deleteEvaluationId");
                setFlashMessage('Evaluation not found.', 'error');
                redirect('/evaluation/list.php');
            }
            
            error_log("DELETE_EVALUATION_ATTEMPT - Evaluation ID: $deleteEvaluationId, Employee: " .
                     $evaluationToDelete['employee_first_name'] . ' ' . $evaluationToDelete['employee_last_name']);
            
            // Perform deletion
            $deleted = $evaluationClass->deleteEvaluation($deleteEvaluationId);
            
            if ($deleted) {
                error_log("DELETE_EVALUATION_SUCCESS - Evaluation ID: $deleteEvaluationId deleted successfully");
                setFlashMessage('Evaluation deleted successfully.', 'success');
                redirect('/evaluation/list.php');
            } else {
                error_log("DELETE_EVALUATION_ERROR - Failed to delete evaluation ID: $deleteEvaluationId");
                setFlashMessage('Failed to delete evaluation. Please try again.', 'error');
                redirect('/evaluation/view.php?id=' . $deleteEvaluationId);
            }
            
        } catch (Exception $e) {
            error_log("DELETE_EVALUATION_EXCEPTION - " . $e->getMessage());
            setFlashMessage('Error deleting evaluation: ' . $e->getMessage(), 'error');
            redirect('/evaluation/view.php?id=' . $deleteEvaluationId);
        }
    }
}

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? null;
if (!$evaluationId) {
    redirect('/evaluation/list.php');
}

// Initialize classes
$evaluationClass = new Evaluation();

// Get evaluation details with job template dimensions
$evaluation = $evaluationClass->getJobTemplateEvaluation($evaluationId);
if (!$evaluation) {
    setFlashMessage('Evaluation not found.', 'error');
    redirect('/evaluation/list.php');
}

// Check permissions using the enhanced authorization function
if (!canAccessEvaluation($evaluation)) {
    setFlashMessage('You do not have permission to view this evaluation.', 'error');
    redirect('/dashboard.php');
}

// Get user role and user ID for template usage
$userRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

$pageTitle = 'Evaluation Details - ' . $evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name'];
$pageHeader = true;
$pageDescription = 'View job template-based performance evaluation details';

// Helper function to get score labels
function getScoreLabel($score) {
    if ($score >= 4.5) return 'Excellent';
    if ($score >= 3.5) return 'Good';
    if ($score >= 2.5) return 'Satisfactory';
    if ($score >= 1.5) return 'Needs Improvement';
    return 'Unsatisfactory';
}

// Check workflow status for debugging/admin purposes
$workflowStatus = null;
if ($userRole === 'hr_admin') {
    try {
        $workflowStatus = $evaluationClass->checkWorkflowStatus($evaluation['employee_id']);
    } catch (Exception $e) {
        error_log("Workflow status check error: " . $e->getMessage());
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Performance Evaluation</h5>
                        <div class="text-muted">
                            <strong>Employee:</strong> <?php echo htmlspecialchars($evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name']); ?> |
                            <strong>Position:</strong> <?php echo htmlspecialchars($evaluation['job_template_title'] ?? 'N/A'); ?> |
                            <strong>Department:</strong> <?php echo htmlspecialchars($evaluation['department'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div>
                        <?php
                        $statusClass = [
                            'draft' => 'warning',
                            'submitted' => 'info',
                            'approved' => 'success',
                            'rejected' => 'danger'
                        ][$evaluation['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?> fs-6">
                            <?php echo ucfirst($evaluation['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Workflow Status (Admin Only) -->
                <?php if ($userRole === 'hr_admin' && $workflowStatus): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <?php if (!$workflowStatus['valid']): ?>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Workflow Issue Detected
                            </h6>
                            <p class="mb-2"><strong>Step:</strong> <?php echo ucfirst(str_replace('_', ' ', $workflowStatus['step'])); ?></p>
                            <p class="mb-2"><strong>Issue:</strong> <?php echo htmlspecialchars($workflowStatus['message']); ?></p>
                            <p class="mb-0"><strong>Action:</strong> <?php echo htmlspecialchars($workflowStatus['action']); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Workflow Status:</strong> All prerequisites met
                            (Job Template: <?php echo htmlspecialchars($workflowStatus['job_template_title']); ?>)
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Evaluation Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-group">
                            <h6 class="text-muted mb-2">Evaluation Period</h6>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($evaluation['period_name']); ?></strong></p>
                            <p class="text-muted small"><?php echo formatDate($evaluation['start_date']) . ' - ' . formatDate($evaluation['end_date']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <h6 class="text-muted mb-2">Evaluator</h6>
                            <p class="mb-1"><?php echo htmlspecialchars(($evaluation['evaluator_first_name'] ?? '') . ' ' . ($evaluation['evaluator_last_name'] ?? '') ?: 'N/A'); ?></p>
                            <p class="text-muted small">Created: <?php echo formatDate($evaluation['created_at']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Overall Rating -->
                <?php if ($evaluation['overall_rating']): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Overall Rating</h6>
                                <div class="display-4 text-primary mb-2"><?php echo number_format($evaluation['overall_rating'], 1); ?>/5.0</div>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $evaluation['overall_rating'] ? 'text-warning' : 'text-muted'; ?> fs-4"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted">
                                    <?php
                                    $rating = $evaluation['overall_rating'];
                                    if ($rating >= 4.5) echo 'Excellent Performance';
                                    elseif ($rating >= 3.5) echo 'Good Performance';
                                    elseif ($rating >= 2.5) echo 'Satisfactory Performance';
                                    elseif ($rating >= 1.5) echo 'Needs Improvement';
                                    else echo 'Unsatisfactory Performance';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Job Template Evaluation Sections -->
                
                <!-- Check if evaluation has job template data -->
                <?php
                $hasJobTemplateData = !empty($evaluation['kpi_results']) ||
                                     !empty($evaluation['competency_results']) ||
                                     !empty($evaluation['responsibility_results']) ||
                                     !empty($evaluation['value_results']);
                ?>
                
                <?php if (!$hasJobTemplateData): ?>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Legacy Evaluation Format
                    </h6>
                    <p class="mb-0">This evaluation was created using the legacy format and does not contain job template dimensions. Please contact HR to update this evaluation to the new job template-based system.</p>
                </div>
                <?php endif; ?>
                
                <!-- KPIs Section -->
                <?php if (!empty($evaluation['kpi_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Key Performance Indicators (KPIs)
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['kpis']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['kpi_results'] as $kpi): ?>
                    <div class="kpi-item mb-3 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($kpi['kpi_name']); ?></h6>
                                <p class="text-muted mb-2">
                                    <strong>Category:</strong> <?php echo htmlspecialchars($kpi['category']); ?> |
                                    <strong>Target:</strong> <?php echo number_format($kpi['target_value'], 2); ?> <?php echo htmlspecialchars($kpi['measurement_unit']); ?>
                                    <?php if ($kpi['achieved_value']): ?>
                                        | <strong>Achieved:</strong> <?php echo number_format($kpi['achieved_value'], 2); ?> <?php echo htmlspecialchars($kpi['measurement_unit']); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($kpi['comments']): ?>
                                <div class="mt-2">
                                    <strong>Comments:</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($kpi['comments'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($kpi['score']): ?>
                                <div class="score-display">
                                    <div class="h4 text-primary mb-1"><?php echo number_format($kpi['score'], 1); ?>/5.0</div>
                                    <div class="rating-stars mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $kpi['score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo getScoreLabel($kpi['score']); ?></small>
                                </div>
                                <?php else: ?>
                                <div class="text-muted">Not scored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Competencies Section -->
                <?php if (!empty($evaluation['competency_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Skills, Knowledge, and Competencies
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['competencies']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['competency_results'] as $competency): ?>
                    <div class="competency-item mb-3 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($competency['competency_name']); ?></h6>
                                <p class="text-muted mb-2">
                                    <strong>Category:</strong> <?php echo htmlspecialchars($competency['category_name'] ?? 'N/A'); ?> |
                                    <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $competency['competency_type'])); ?> |
                                    <strong>Required Level:</strong> <?php echo ucfirst($competency['required_level']); ?>
                                    <?php if ($competency['achieved_level']): ?>
                                        | <strong>Achieved Level:</strong> <?php echo ucfirst($competency['achieved_level']); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($competency['comments']): ?>
                                <div class="mt-2">
                                    <strong>Comments:</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($competency['comments'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($competency['score']): ?>
                                <div class="score-display">
                                    <div class="h4 text-primary mb-1"><?php echo number_format($competency['score'], 1); ?>/5.0</div>
                                    <div class="rating-stars mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $competency['score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo getScoreLabel($competency['score']); ?></small>
                                </div>
                                <?php else: ?>
                                <div class="text-muted">Not scored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Responsibilities Section -->
                <?php if (!empty($evaluation['responsibility_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Key Responsibilities
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['responsibilities']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['responsibility_results'] as $responsibility): ?>
                    <div class="responsibility-item mb-3 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-2">Responsibility #<?php echo $responsibility['sort_order']; ?></h6>
                                <p class="mb-2"><?php echo htmlspecialchars($responsibility['responsibility_text']); ?></p>
                                
                                <?php if ($responsibility['comments']): ?>
                                <div class="mt-2">
                                    <strong>Comments:</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($responsibility['comments'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($responsibility['score']): ?>
                                <div class="score-display">
                                    <div class="h4 text-primary mb-1"><?php echo number_format($responsibility['score'], 1); ?>/5.0</div>
                                    <div class="rating-stars mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $responsibility['score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo getScoreLabel($responsibility['score']); ?></small>
                                </div>
                                <?php else: ?>
                                <div class="text-muted">Not scored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Company Values Section -->
                <?php if (!empty($evaluation['value_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Living Our Values
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['values']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['value_results'] as $value): ?>
                    <div class="value-item mb-3 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($value['value_name']); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($value['description']); ?></p>
                                
                                <?php if ($value['comments']): ?>
                                <div class="mt-2">
                                    <strong>Comments:</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($value['comments'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($value['score']): ?>
                                <div class="score-display">
                                    <div class="h4 text-primary mb-1"><?php echo number_format($value['score'], 1); ?>/5.0</div>
                                    <div class="rating-stars mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $value['score'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo getScoreLabel($value['score']); ?></small>
                                </div>
                                <?php else: ?>
                                <div class="text-muted">Not scored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Overall Comments -->
                <?php if ($evaluation['overall_comments']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">Overall Comments</h6>
                    <div class="section-content">
                        <p><?php echo nl2br(htmlspecialchars($evaluation['overall_comments'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Development and Goals -->
                <?php if ($evaluation['strengths'] || $evaluation['development_areas'] || $evaluation['goals_next_period']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">Development and Goals</h6>
                    
                    <?php if ($evaluation['strengths']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-success">Strengths</h6>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['strengths'])); ?></p>
                        </div>
                        <?php if ($evaluation['development_areas']): ?>
                        <div class="col-md-6">
                            <h6 class="text-warning">Development Areas</h6>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['development_areas'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($evaluation['goals_next_period']): ?>
                    <div class="mb-3">
                        <h6 class="text-info">Goals for Next Period</h6>
                        <p><?php echo nl2br(htmlspecialchars($evaluation['goals_next_period'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="/evaluation/list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                    <div>
                        <?php if (canEditEvaluation($evaluation)): ?>
                        <a href="/evaluation/edit.php?id=<?php echo $evaluationId; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>
                            <?php if ($evaluation['status'] === 'submitted' && $userRole === 'hr_admin'): ?>
                                Review Evaluation
                            <?php else: ?>
                                Edit Evaluation
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'hr_admin'): ?>
                        <div class="btn-group ms-2">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="printEvaluation()">
                                    <i class="fas fa-print me-2"></i>Print
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportEvaluation()">
                                    <i class="fas fa-download me-2"></i>Export PDF
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteEvaluation()">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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

.section-content {
    color: #6c757d;
    line-height: 1.6;
}

.info-group h6 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rating-stars .fa-star {
    margin: 0 2px;
}

.kpi-item, .competency-item, .responsibility-item, .value-item {
    transition: all 0.2s ease;
}

.kpi-item:hover, .competency-item:hover, .responsibility-item:hover, .value-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.score-display {
    text-align: center;
}

.score-display .rating-stars {
    font-size: 0.9rem;
}

.score-display .rating-stars .fa-star {
    margin: 0 1px;
}
</style>

<script>
function printEvaluation() {
    window.print();
}

function exportEvaluation() {
    // This would typically generate a PDF
    alert('PDF export functionality - to be implemented');
}

function deleteEvaluation() {
    console.log('DELETE_EVALUATION_CALLED - Evaluation ID: <?php echo $evaluationId; ?>');
    console.log('DELETE_EVALUATION_CALLED - User Role: <?php echo $userRole; ?>');
    
    if (confirm('Are you sure you want to delete this evaluation? This action cannot be undone.')) {
        console.log('DELETE_EVALUATION_CONFIRMED - Starting delete process');
        
        // Create form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/evaluation/view.php?id=<?php echo $evaluationId; ?>';
        
        // Add CSRF token if available
        <?php if (isset($_SESSION['csrf_token'])): ?>
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
        form.appendChild(csrfInput);
        <?php endif; ?>
        
        // Add action input
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        // Add evaluation ID
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'evaluation_id';
        idInput.value = '<?php echo $evaluationId; ?>';
        form.appendChild(idInput);
        
        console.log('DELETE_EVALUATION_FORM_CREATED - Submitting form');
        document.body.appendChild(form);
        form.submit();
    } else {
        console.log('DELETE_EVALUATION_CANCELLED - User cancelled deletion');
    }
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>