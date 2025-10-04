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
                            <a href="/evaluation/list.php?status=reviewed" class="btn btn-outline-primary <?php echo $status === 'reviewed' ? 'active' : ''; ?>">
                                Reviewed
                            </a>
                            <a href="/evaluation/list.php?status=approved" class="btn btn-outline-success <?php echo $status === 'approved' ? 'active' : ''; ?>">
                                Approved
                            </a>
                        </div>
                    </div>
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
                        <?php elseif ($status === 'reviewed'): ?>
                            No reviewed evaluations found.
                        <?php elseif ($status === 'approved'): ?>
                            No approved evaluations found.
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
                                <th>Status</th>
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
                                    <?php
                                    $statusClass = [
                                        'draft' => 'warning',
                                        'submitted' => 'info',
                                        'reviewed' => 'primary',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ][$evaluation['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($evaluation['status']); ?>
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