<?php
/**
 * Employee Edit Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';
require_once __DIR__ . '/../../classes/User.php';

// Require authentication
requireAuth();

// Get employee ID from URL
$employeeId = $_GET['id'] ?? null;
if (!$employeeId) {
    redirect('/employees/list.php');
}

// Check permissions - HR Admin can edit anyone, employees can only edit themselves
$userRole = $_SESSION['user_role'] ?? '';
$currentEmployeeId = $_SESSION['employee_id'] ?? null;

if (!isHRAdmin() && $currentEmployeeId != $employeeId) {
    setFlashMessage('You can only edit your own profile.', 'error');
    redirect('/dashboard.php');
}

// Determine if this is a self-edit (employee editing their own profile)
$isSelfEdit = ($currentEmployeeId == $employeeId);

// Initialize classes
$employeeClass = new Employee();
$jobTemplateClass = new JobTemplate();

// Get employee details
// HR Admins can view/edit inactive employees, regular employees cannot
$includeInactive = isHRAdmin();
$employee = $employeeClass->getEmployeeById($employeeId, $includeInactive);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

$pageTitle = $isSelfEdit ? 'My Profile - ' . $employee['first_name'] . ' ' . $employee['last_name'] : 'Edit Employee - ' . $employee['first_name'] . ' ' . $employee['last_name'];
$pageHeader = true;
$pageDescription = $isSelfEdit ? 'Update your profile information' : 'Update employee information';

// Handle form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Base fields that employees can edit about themselves (employees table)
        $employeeUpdateData = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone']) ?: null,
            'address' => trim($_POST['address']) ?: null,
        ];

        // Additional employee fields only HR Admin can modify
        if (isHRAdmin()) {
            $employeeUpdateData['position'] = trim($_POST['position'] ?? '') ?: null;
            $employeeUpdateData['department'] = trim($_POST['department'] ?? '') ?: null;
            $employeeUpdateData['manager_id'] = ($_POST['manager_id'] ?? '') ?: null;
            $employeeUpdateData['hire_date'] = ($_POST['hire_date'] ?? '') ?: null;
            $employeeUpdateData['active'] = isset($_POST['active']) ? 1 : 0;
            
            // Add job_template_id (including empty values for unassignment)
            if (isset($_POST['job_template_id'])) {
                $employeeUpdateData['job_template_id'] = $_POST['job_template_id'] ?: null;
            }
        }

        
        // Update employee table
        $result = $employeeClass->updateEmployee($employeeId, $employeeUpdateData);
        
        // Update user table fields (HR Admin only)
        if (isHRAdmin() && !empty($employee['user_id'])) {
            $userClass = new User();
            $userUpdateData = [];
            
            if (!empty($_POST['email'])) {
                $userUpdateData['email'] = trim($_POST['email']);
            }
            if (!empty($_POST['username'])) {
                $userUpdateData['username'] = trim($_POST['username']);
            }
            if (!empty($_POST['role'])) {
                $userUpdateData['role'] = $_POST['role'];
            }
            
            if (!empty($userUpdateData)) {
                $userResult = $userClass->updateUser($employee['user_id'], $userUpdateData);
                if (!$userResult) {
                    throw new Exception('Failed to update user account information');
                }
            }
        }
        
        if ($result) {
            setFlashMessage('Employee updated successfully!', 'success');
            redirect('/employees/view.php?id=' . $employeeId);
        } else {
            setFlashMessage('Failed to update employee. Please try again.', 'error');
        }
    } catch (Exception $e) {
        error_log('Employee update error: ' . $e->getMessage());
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Get potential managers
$potentialManagers = $employeeClass->getPotentialManagers($employeeId);

// Get departments and job templates
$departments = $employeeClass->getDepartments();
$jobTemplates = $jobTemplateClass->getJobTemplates();

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo $isSelfEdit ? 'Edit My Profile' : 'Edit Employee Information'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
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

                    <?php if (isHRAdmin()): ?>
                    <!-- User Account Fields (HR Admin only) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo htmlspecialchars($employee['username'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">User Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="employee" <?php echo ($employee['role'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                    <option value="manager" <?php echo ($employee['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="hr_admin" <?php echo ($employee['role'] ?? '') === 'hr_admin' ? 'selected' : ''; ?>>HR Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Empty for spacing -->
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

                    <?php else: ?>
                    <!-- Read-only fields for self-edit -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['position'] ?? 'Not assigned'); ?>" readonly>
                                <div class="form-text">Contact HR to change your position.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['department'] ?? 'Not assigned'); ?>" readonly>
                                <div class="form-text">Contact HR to change your department.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isHRAdmin()): ?>
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
                                <label for="job_template_id" class="form-label">Job Template</label>
                                <select class="form-select" id="job_template_id" name="job_template_id">
                                    <option value="">No Job Template</option>
                                    <?php foreach ($jobTemplates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>"
                                            <?php echo ($employee['job_template_id'] ?? '') == $template['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($template['position_title']); ?>
                                        <?php if ($template['department']): ?>
                                            - <?php echo htmlspecialchars($template['department']); ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Assign a job template to define evaluation criteria for this employee.
                                    <br><small class="text-warning">Note: Job template assignment requires database migration. See sql/add_job_template_id_to_employees.sql</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Empty column for spacing -->
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Phone and Address - available to all users -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <?php if (isHRAdmin()): ?>
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
                        <?php endif; ?>
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
                            <button type="submit" class="btn btn-primary" onclick="console.log('Submit button clicked'); return true;">
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