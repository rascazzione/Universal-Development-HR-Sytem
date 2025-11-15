<?php
/**
 * Evaluation List Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require authentication
requireAuth();

$pageTitle = 'All Evaluations';
$pageHeader = true;
$pageDescription = 'View and manage performance evaluations';

// Initialize classes
$evaluationClass = new Evaluation();
$employeeClass = new Employee();
$periodClass = new EvaluationPeriod();

// Get filter parameters
$status = $_GET['status'] ?? '';
$employee_id = $_GET['employee_id'] ?? '';

// Build filter conditions
$filters = [];
if ($status) {
    $filters['status'] = $status;
    $pageTitle = ucfirst($status) . ' Evaluations';
}
if ($employee_id) {
    $filters['employee_id'] = $employee_id;
}

// Get evaluations based on user role and filters
$userRole = $_SESSION['user_role'];
if ($userRole === 'employee') {
    // Employees can only see their own evaluations
    $evaluations = $evaluationClass->getEmployeeEvaluations($_SESSION['employee_id']);
} elseif ($userRole === 'manager') {
    // FIXED: Managers can see evaluations for their direct reports via manager_id relationship
    $managerId = $_SESSION['employee_id'];
    $evaluations = $evaluationClass->getManagerEvaluations($managerId, $filters);
} else {
    // HR Admin can see all evaluations
    $evaluationsData = $evaluationClass->getEvaluations(1, 50, $filters);
    $evaluations = $evaluationsData['evaluations'];
}

// Get active evaluation periods for workflow actions
$activePeriods = [];
if ($userRole === 'employee') {
    $activePeriod = $periodClass->getActivePeriodForEmployee($_SESSION['employee_id']);
    if ($activePeriod) {
        $activePeriods[] = $activePeriod;
    }
} elseif ($userRole === 'manager') {
    $activePeriods = $periodClass->getActivePeriods();
} else {
    $activePeriods = $periodClass->getActivePeriods();
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?php echo $pageTitle; ?>
                    <?php if ($status === 'draft'): ?>
                        <span class="badge bg-warning ms-2">Pending</span>
                    <?php endif; ?>
                </h5>
                <?php if ($userRole !== 'employee'): ?>
                <a href="/evaluation/create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Evaluation
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Filter Options -->
                <?php if ($userRole !== 'employee'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="/evaluation/list.php" class="btn btn-outline-secondary <?php echo !$status ? 'active' : ''; ?>">
                                All Evaluations
                            </a>
                            <a href="/evaluation/list.php?status=draft" class="btn btn-outline-warning <?php echo $status === 'draft' ? 'active' : ''; ?>">
                                Draft
                            </a>
                            <a href="/evaluation/list.php?status=submitted" class="btn btn-outline-info <?php echo $status === 'submitted' ? 'active' : ''; ?>">
                                Submitted
                            </a>
                            <a href="/evaluation/list.php?status=approved" class="btn btn-outline-success <?php echo $status === 'approved' ? 'active' : ''; ?>">
                                Approved
                            </a>
                            <a href="/evaluation/list.php?status=rejected" class="btn btn-outline-danger <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                                Rejected
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Workflow Status for Active Periods -->
                <?php if (!empty($activePeriods)): ?>
                <div class="row mb-4">
                    <?php foreach ($activePeriods as $period): ?>
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($period['period_name']); ?>
                                </h6>
                                <small><?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?></small>
                            </div>
                            <div class="card-body">
                                <?php
                                $workflowStatus = [];
                                if ($userRole === 'employee') {
                                    $workflowStatus = (new EvaluationWorkflow())->getWorkflowStatus($_SESSION['employee_id'], $period['period_id']);
                                }
                                ?>

                                <?php if ($userRole === 'employee'): ?>
                                    <?php if ($workflowStatus['has_self'] && $workflowStatus['self_state'] === 'pending_self'): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Action Required:</strong> Complete your self-evaluation
                                            <a href="/evaluation/self-evaluation.php" class="btn btn-sm btn-warning ms-2">Complete Now</a>
                                        </div>
                                    <?php elseif ($workflowStatus['has_final'] && $workflowStatus['final_state'] === 'final_delivered'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>
                                            Your final evaluation is ready to view.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Self-evaluation period is active.
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($userRole === 'manager'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-users me-2"></i>
                                        Review team evaluations for this period.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-cogs me-2"></i>
                                        Manage evaluations for this period.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($evaluations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">
                        <?php if ($status === 'draft'): ?>
                            No draft evaluations found.
                        <?php elseif ($status === 'submitted'): ?>
                            No submitted evaluations found.
                        <?php elseif ($status === 'approved'): ?>
                            No approved evaluations found.
                        <?php elseif ($status === 'rejected'): ?>
                            No rejected evaluations found.
                        <?php else: ?>
                            No evaluations found.
                        <?php endif; ?>
                    </p>
                    <?php if ($userRole !== 'employee'): ?>
                    <a href="/evaluation/create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create First Evaluation
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Period</th>
                                <th>Evaluator</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Workflow State</th>
                                <th>Overall Rating</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $evaluation): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <div class="avatar-title bg-primary rounded-circle">
                                                <?php echo strtoupper(substr($evaluation['employee_first_name'], 0, 1) . substr($evaluation['employee_last_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($evaluation['position'] ?? 'N/A'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($evaluation['period_name'] ?? 'N/A'); ?>
                                    <br><small class="text-muted"><?php echo formatDate($evaluation['start_date']) . ' - ' . formatDate($evaluation['end_date']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(($evaluation['evaluator_first_name'] ?? '') . ' ' . ($evaluation['evaluator_last_name'] ?? '') ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($evaluation['evaluation_type'] ?? 'manager'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'draft' => 'warning',
                                        'submitted' => 'info',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ][$evaluation['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($evaluation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $workflowClass = [
                                        'pending_self' => 'warning',
                                        'self_submitted' => 'info',
                                        'pending_manager' => 'primary',
                                        'manager_submitted' => 'success',
                                        'final_delivered' => 'success'
                                    ][$evaluation['workflow_state']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $workflowClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['workflow_state'] ?? 'pending_self')); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($evaluation['overall_rating']): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?php echo number_format($evaluation['overall_rating'], 1); ?>/5.0</span>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $evaluation['overall_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not rated</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($evaluation['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/evaluation/view.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (canEditEvaluation($evaluation)): ?>
                                        <a href="/evaluation/edit.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-outline-secondary" title="<?php echo ($evaluation['status'] === 'submitted' && $userRole === 'hr_admin') ? 'Review' : 'Edit'; ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php
                                        // Workflow-specific actions
                                        $workflowState = $evaluation['workflow_state'] ?? 'pending_self';
                                        $evaluationType = $evaluation['evaluation_type'] ?? 'manager';

                                        // Employee: Show self-evaluation action for pending self-evaluations
                                        if ($userRole === 'employee' && $evaluationType === 'self' && $workflowState === 'pending_self'): ?>
                                            <a href="/evaluation/self-evaluation.php" class="btn btn-outline-success btn-sm" title="Complete Self-Evaluation">
                                                <i class="fas fa-user-check"></i> Complete
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                        // Manager: Show review action for submitted self-evaluations
                                        if ($userRole === 'manager' && $evaluationType === 'self' && $workflowState === 'self_submitted'): ?>
                                            <a href="/evaluation/manager-review.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-outline-warning btn-sm" title="Review Self-Evaluation">
                                                <i class="fas fa-user-tie"></i> Review
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                        // Manager: Show evaluation action for pending manager evaluations
                                        if ($userRole === 'manager' && $evaluationType === 'manager' && $workflowState === 'pending_manager'): ?>
                                            <a href="/evaluation/manager-review.php?id=<?php echo $evaluation['self_evaluation_id']; ?>" class="btn btn-outline-primary btn-sm" title="Complete Manager Evaluation">
                                                <i class="fas fa-edit"></i> Evaluate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>