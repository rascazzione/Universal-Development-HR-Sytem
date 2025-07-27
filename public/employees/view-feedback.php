<?php
/**
 * View Feedback Entries Page
 * Growth Evidence System - Continuous Performance Management
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/GrowthEvidenceJournal.php';
require_once __DIR__ . '/../../classes/MediaManager.php';

// Require authentication
requireAuth();

$pageTitle = 'Feedback History';
$pageHeader = true;
$pageDescription = 'View feedback entries in the Growth Evidence System';

// Initialize classes
$employeeClass = new Employee();
$journalClass = new GrowthEvidenceJournal();

// Get employee ID from URL
$employeeId = $_GET['employee_id'] ?? null;
if (!$employeeId) {
    setFlashMessage('Employee ID is required.', 'error');
    redirect('/employees/list.php');
}

// Get employee details
$employee = $employeeClass->getEmployeeById($employeeId);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

// Check permissions
$userRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];
$currentEmployeeId = $_SESSION['employee_id'] ?? null;

// Users can only view their own feedback or feedback they gave
// Managers can view feedback for their direct reports
// HR admins can view all feedback
$canView = false;
if ($userRole === 'hr_admin') {
    $canView = true;
} elseif ($userRole === 'manager' && $employee['manager_id'] == $currentEmployeeId) {
    $canView = true;
} elseif ($employeeId == $currentEmployeeId) {
    $canView = true;
}

if (!$canView) {
    setFlashMessage('You do not have permission to view feedback for this employee.', 'error');
    redirect('/dashboard.php');
}

// Get feedback entries for this employee
$entries = $journalClass->getEmployeeJournal($employeeId);

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Feedback History for <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h5>
                <?php if ($userRole !== 'employee'): ?>
                <a href="/employees/give-feedback.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Give Feedback
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($entries)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-comment-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No feedback entries found for this employee.</p>
                    <?php if ($userRole !== 'employee'): ?>
                    <a href="/employees/give-feedback.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Give First Feedback
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Dimension</th>
                                <th>Rating</th>
                                <th>Content</th>
                                <th>Manager</th>
                                <th>Attachments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo formatDate($entry['entry_date']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($entry['dimension']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star rating-star <?php echo $i <= $entry['star_rating'] ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1"><?php echo $entry['star_rating']; ?>/5</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 300px; word-wrap: break-word;">
                                        <?php echo htmlspecialchars(substr($entry['content'], 0, 100)); ?>
                                        <?php if (strlen($entry['content']) > 100): ?>
                                            ...
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($entry['manager_first_name'] . ' ' . $entry['manager_last_name']); ?></td>
                                <td>
                                    <?php if ($entry['attachment_count'] > 0): ?>
                                        <span class="badge bg-info">
                                            <?php echo $entry['attachment_count']; ?> 
                                            <?php echo $entry['attachment_count'] == 1 ? 'attachment' : 'attachments'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
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
