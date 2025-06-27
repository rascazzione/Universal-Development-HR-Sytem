<?php
/**
 * Employee View Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';

// Require authentication
requireAuth();

// Get employee ID from URL
$employeeId = $_GET['id'] ?? null;
if (!$employeeId) {
    redirect('/employees/list.php');
}

// Initialize classes
$employeeClass = new Employee();
$evaluationClass = new Evaluation();
$jobTemplateClass = new JobTemplate();

// Get employee details
// HR Admins can view inactive employees, regular employees cannot
$includeInactive = isHRAdmin();
$employee = $employeeClass->getEmployeeById($employeeId, $includeInactive);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

// Check permissions with better error handling
$userRole = $_SESSION['user_role'] ?? '';
$currentUserId = $_SESSION['user_id'] ?? null;
$currentEmployeeId = $_SESSION['employee_id'] ?? null;

// Use the canAccessEmployee function for proper permission checking
if (!canAccessEmployee($employeeId)) {
    setFlashMessage('You do not have permission to view this employee.', 'error');
    redirect('/dashboard.php');
}

$pageTitle = 'Employee Details - ' . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
$pageHeader = true;
$pageDescription = 'View employee information and evaluation history';

// Get employee evaluations with error handling
$evaluations = [];
try {
    $evaluationResult = $evaluationClass->getEvaluations(1, 1000, ['employee_id' => $employeeId]);
    $evaluations = $evaluationResult['evaluations'] ?? [];
} catch (Exception $e) {
    error_log('Error fetching employee evaluations: ' . $e->getMessage());
    setFlashMessage('Warning: Could not load evaluation history.', 'warning');
}

// Get job template information if assigned with error handling
$jobTemplate = null;
if (!empty($employee['job_template_id'])) {
    try {
        $jobTemplate = $jobTemplateClass->getJobTemplateById($employee['job_template_id']);
    } catch (Exception $e) {
        error_log('Error fetching job template: ' . $e->getMessage());
        // Don't show error to user for job template, just log it
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <!-- Employee Info Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Employee Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-lg mx-auto">
                        <div class="avatar-title bg-primary rounded-circle fs-2">
                            <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></p>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="fw-medium">Employee ID:</td>
                            <td><?php echo htmlspecialchars($employee['employee_number']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Email:</td>
                            <td><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Department:</td>
                            <td><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Position:</td>
                            <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Manager:</td>
                            <td>
                                <?php if ($employee['manager_first_name']): ?>
                                    <?php echo htmlspecialchars($employee['manager_first_name'] . ' ' . $employee['manager_last_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No Manager</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Hire Date:</td>
                            <td><?php echo $employee['hire_date'] ? formatDate($employee['hire_date']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Phone:</td>
                            <td><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Job Template:</td>
                            <td>
                                <?php if ($jobTemplate): ?>
                                    <a href="/admin/job_templates.php?edit=<?php echo $jobTemplate['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($jobTemplate['position_title']); ?>
                                    </a>
                                    <?php if ($jobTemplate['department']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($jobTemplate['department']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No Job Template Assigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Status:</td>
                            <td>
                                <span class="badge bg-<?php echo $employee['active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $employee['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php if (isHRAdmin()): ?>
                <div class="d-grid gap-2 mt-3">
                    <a href="/employees/edit.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Employee
                    </a>
                    <a href="/evaluation/create.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Create Evaluation
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Evaluation History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Evaluation History</h5>
                <?php if ($userRole !== 'employee'): ?>
                <a href="/evaluation/create.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>New Evaluation
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($evaluations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No evaluations found for this employee.</p>
                    <?php if ($userRole !== 'employee'): ?>
                    <a href="/evaluation/create.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create First Evaluation
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Evaluator</th>
                                <th>Status</th>
                                <th>Overall Rating</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $evaluation): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($evaluation['period_name']); ?></div>
                                    <small class="text-muted"><?php echo formatDate($evaluation['start_date']) . ' - ' . formatDate($evaluation['end_date']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(($evaluation['evaluator_first_name'] ?? '') . ' ' . ($evaluation['evaluator_last_name'] ?? '') ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'draft' => 'warning',
                                        'completed' => 'info',
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