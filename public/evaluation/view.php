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

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? null;
if (!$evaluationId) {
    redirect('/evaluation/list.php');
}

// Initialize classes
$evaluationClass = new Evaluation();

// Get evaluation details
$evaluation = $evaluationClass->getEvaluationById($evaluationId);
if (!$evaluation) {
    setFlashMessage('Evaluation not found.', 'error');
    redirect('/evaluation/list.php');
}

// Check permissions
$userRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];
$currentEmployeeId = $_SESSION['employee_id'] ?? null;

// Allow viewing if user is HR admin, manager, evaluator, or the employee being evaluated
$canView = ($userRole === 'hr_admin' || 
           $userRole === 'manager' || 
           $evaluation['evaluator_id'] == $currentUserId ||
           $evaluation['employee_id'] == $currentEmployeeId);

if (!$canView) {
    setFlashMessage('You do not have permission to view this evaluation.', 'error');
    redirect('/dashboard.php');
}

$pageTitle = 'Evaluation Details - ' . $evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name'];
$pageHeader = true;
$pageDescription = 'View performance evaluation details';

// Get evaluation template for section titles
$template = $evaluationClass->getEvaluationTemplate();

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
                            <strong>Position:</strong> <?php echo htmlspecialchars($evaluation['position'] ?? 'N/A'); ?> |
                            <strong>Department:</strong> <?php echo htmlspecialchars($evaluation['department'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div>
                        <?php
                        $statusClass = [
                            'draft' => 'warning',
                            'completed' => 'info',
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

                <!-- Evaluation Sections -->
                <?php if ($evaluation['expected_results']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        <?php echo $template['expected_results']['title']; ?>
                        <?php if ($evaluation['expected_results_score']): ?>
                            <span class="badge bg-primary ms-2"><?php echo number_format($evaluation['expected_results_score'], 1); ?>/5.0</span>
                        <?php endif; ?>
                    </h6>
                    <div class="section-content">
                        <?php if (isset($evaluation['expected_results']['comments'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['expected_results']['comments'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No comments provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($evaluation['skills_competencies']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        <?php echo $template['skills_competencies']['title']; ?>
                        <?php if ($evaluation['skills_competencies_score']): ?>
                            <span class="badge bg-primary ms-2"><?php echo number_format($evaluation['skills_competencies_score'], 1); ?>/5.0</span>
                        <?php endif; ?>
                    </h6>
                    <div class="section-content">
                        <?php if (isset($evaluation['skills_competencies']['comments'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['skills_competencies']['comments'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No comments provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($evaluation['key_responsibilities']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        <?php echo $template['key_responsibilities']['title']; ?>
                        <?php if ($evaluation['key_responsibilities_score']): ?>
                            <span class="badge bg-primary ms-2"><?php echo number_format($evaluation['key_responsibilities_score'], 1); ?>/5.0</span>
                        <?php endif; ?>
                    </h6>
                    <div class="section-content">
                        <?php if (isset($evaluation['key_responsibilities']['comments'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['key_responsibilities']['comments'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No comments provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($evaluation['living_values']): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        <?php echo $template['living_values']['title']; ?>
                        <?php if ($evaluation['living_values_score']): ?>
                            <span class="badge bg-primary ms-2"><?php echo number_format($evaluation['living_values_score'], 1); ?>/5.0</span>
                        <?php endif; ?>
                    </h6>
                    <div class="section-content">
                        <?php if (isset($evaluation['living_values']['comments'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($evaluation['living_values']['comments'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No comments provided.</p>
                        <?php endif; ?>
                    </div>
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
                        <?php if ($evaluation['status'] === 'draft' && ($userRole !== 'employee' || $evaluation['evaluator_id'] == $currentUserId)): ?>
                        <a href="/evaluation/edit.php?id=<?php echo $evaluationId; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Evaluation
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
    if (confirm('Are you sure you want to delete this evaluation? This action cannot be undone.')) {
        // This would typically submit a delete request
        alert('Delete functionality - to be implemented');
    }
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>