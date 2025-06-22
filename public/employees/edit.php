<?php
/**
 * Employee Edit Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require HR Admin access
requireAuth();
if (!isHRAdmin()) {
    redirect('/dashboard.php');
}

// Get employee ID from URL
$employeeId = $_GET['id'] ?? null;
if (!$employeeId) {
    redirect('/employees/list.php');
}

// Initialize Employee class
$employeeClass = new Employee();

// Get employee details
$employee = $employeeClass->getEmployeeById($employeeId);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

$pageTitle = 'Edit Employee - ' . $employee['first_name'] . ' ' . $employee['last_name'];
$pageHeader = true;
$pageDescription = 'Update employee information';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'position' => trim($_POST['position']) ?: null,
            'department' => trim($_POST['department']) ?: null,
            'manager_id' => $_POST['manager_id'] ?: null,
            'hire_date' => $_POST['hire_date'] ?: null,
            'phone' => trim($_POST['phone']) ?: null,
            'address' => trim($_POST['address']) ?: null,
            'active' => isset($_POST['active']) ? 1 : 0
        ];

        $result = $employeeClass->updateEmployee($employeeId, $updateData);
        
        if ($result) {
            setFlashMessage('Employee updated successfully!', 'success');
            redirect('/employees/view.php?id=' . $employeeId);
        } else {
            setFlashMessage('Failed to update employee. Please try again.', 'error');
        }
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Get potential managers
$potentialManagers = $employeeClass->getPotentialManagers($employeeId);

// Get departments
$departments = $employeeClass->getDepartments();

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Employee Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                <div class="invalid-feedback">Please provide a first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                <div class="invalid-feedback">Please provide a last name.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>" 
                                       list="departments">
                                <datalist id="departments">
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="manager_id" class="form-label">Manager</label>
                                <select class="form-select" id="manager_id" name="manager_id">
                                    <option value="">No Manager</option>
                                    <?php foreach ($potentialManagers as $manager): ?>
                                    <option value="<?php echo $manager['employee_id']; ?>" 
                                            <?php echo $employee['manager_id'] == $manager['employee_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name'] . ' - ' . ($manager['position'] ?? 'N/A')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hire_date" class="form-label">Hire Date</label>
                                <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                       value="<?php echo $employee['hire_date']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="active" name="active" 
                                           <?php echo $employee['active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="active">
                                        Active Employee
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/employees/view.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to View
                        </a>
                        <div>
                            <a href="/employees/list.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Employee Information Display -->
<div class="row justify-content-center mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Current Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Employee Number:</strong> <?php echo htmlspecialchars($employee['employee_number']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($employee['username'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($employee['role'] ?? 'N/A')); ?></p>
                        <p><strong>Created:</strong> <?php echo formatDate($employee['created_at'] ?? null); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo formatDate($employee['updated_at'] ?? null); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>