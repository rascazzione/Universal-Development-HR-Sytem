<?php
/**
 * Team View Page for Managers
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require authentication and manager role
requireAuth();
if (!isManager()) {
    redirect('/dashboard.php');
}

$pageTitle = 'My Team';
$pageHeader = true;
$pageDescription = 'View and manage your team members';

// Initialize Employee class
$employeeClass = new Employee();

// Get current manager's employee ID
$managerId = $_SESSION['employee_id'];

// Get team members (direct reports)
$teamMembers = $employeeClass->getTeamMembers($managerId);

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">My Team</h5>
                    <div class="btn-group">
                        <a href="/evaluation/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Evaluation
                        </a>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        Showing <?php echo count($teamMembers); ?> team member(s) under your direct supervision
                    </small>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($teamMembers)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No team members found.</p>
                    <p class="text-muted">Contact HR to assign team members to your supervision.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamMembers as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['employee_number']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <div class="avatar-title bg-primary rounded-circle">
                                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $employee['active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $employee['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/employees/view.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/evaluation/create.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-success" title="Create Evaluation">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
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

<!-- Team Statistics -->
<?php if (!empty($teamMembers)): ?>
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Total Team Members</h5>
                        <h3 class="mb-0"><?php echo count($teamMembers); ?></h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Active Members</h5>
                        <h3 class="mb-0"><?php echo count(array_filter($teamMembers, fn($e) => $e['active'])); ?></h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-user-check fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Departments</h5>
                        <h3 class="mb-0"><?php echo count(array_unique(array_filter(array_column($teamMembers, 'department')))); ?></h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-building fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                        <div class="btn-group-vertical btn-group-sm">
                            <a href="/evaluation/create.php" class="btn btn-light text-dark">
                                <i class="fas fa-plus me-1"></i>New Evaluation
                            </a>
                            <a href="/dashboard.php" class="btn btn-light text-dark">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-bolt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../templates/footer.php'; ?>