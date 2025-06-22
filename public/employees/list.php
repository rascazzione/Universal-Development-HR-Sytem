<?php
/**
 * Employee List Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require authentication and HR/Manager access
requireAuth();
if (!isHRAdmin() && !isManager()) {
    redirectTo('/dashboard.php');
}

$pageTitle = 'All Employees';
$pageHeader = true;
$pageDescription = 'Manage and view all employees in the system';

// Initialize Employee class
$employeeClass = new Employee();

// Get all employees
$employeesData = $employeeClass->getEmployees(1, 100); // Get first 100 employees
$employees = $employeesData['employees'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Employee List</h5>
                <?php if (isHRAdmin()): ?>
                <a href="/employees/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Employee
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($employees)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No employees found.</p>
                    <?php if (isHRAdmin()): ?>
                    <a href="/employees/add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add First Employee
                    </a>
                    <?php endif; ?>
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
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
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
                                    <?php if ($employee['manager_first_name']): ?>
                                        <?php echo htmlspecialchars($employee['manager_first_name'] . ' ' . $employee['manager_last_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No Manager</span>
                                    <?php endif; ?>
                                </td>
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
                                        <?php if (isHRAdmin()): ?>
                                        <a href="/employees/edit.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
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

<?php include __DIR__ . '/../../templates/footer.php'; ?>