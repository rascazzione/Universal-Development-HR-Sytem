<?php
/**
 * Employee Edit Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Department.php';

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
$departmentClass = new Department();

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
        // Check if this is a password reset request
        if (isset($_POST['action']) && $_POST['action'] === 'reset_password' && isHRAdmin()) {
            // Handle password reset by admin
            $adminPassword = $_POST['admin_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($adminPassword)) {
                throw new Exception('Admin password is required for authorization');
            }
            
            if (empty($newPassword)) {
                throw new Exception('New password is required');
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New password and confirmation do not match');
            }
            
            if (empty($employee['user_id'])) {
                throw new Exception('Employee does not have a user account');
            }
            
            // Reset password using admin method
            $userClass = new User();
            $result = $userClass->resetPasswordByAdmin(
                $_SESSION['user_id'], 
                $adminPassword, 
                $employee['user_id'], 
                $newPassword
            );
            
            if ($result) {
                setFlashMessage('Password reset successfully for ' . $employee['first_name'] . ' ' . $employee['last_name'] . '!', 'success');
                redirect('/employees/view.php?id=' . $employeeId);
            } else {
                throw new Exception('Failed to reset password. Please try again.');
            }
        } else {
            // Handle regular employee update
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
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $allDepartments = $departmentClass->getDepartments();
                                    foreach ($allDepartments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                            <?php echo ($employee['department'] ?? '') == $dept['department_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
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

<!-- Password Reset Section (HR Admin only) -->
<?php if (isHRAdmin() && !empty($employee['user_id'])): ?>
<div class="row justify-content-center mt-4">
    <div class="col-md-8">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="fas fa-key me-2"></i>Reset Employee Password
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Security Notice:</strong> You must enter your own password to authorize this password reset.
                </div>
                
                <button class="btn btn-warning" type="button" data-bs-toggle="collapse" data-bs-target="#passwordResetForm" aria-expanded="false">
                    <i class="fas fa-unlock-alt me-2"></i>Reset Password for <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                </button>
                
                <div class="collapse mt-3" id="passwordResetForm">
                    <form method="POST" id="resetPasswordForm">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Your Password (Admin Authorization) *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordReset('admin_password')">
                                            <i class="fas fa-eye" id="admin_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Enter your own password to authorize this action.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password for Employee *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordReset('new_password')">
                                            <i class="fas fa-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 8 characters.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordReset('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="password-match-feedback-reset">
                                        Passwords do not match.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Important:</strong>
                            <ul class="mb-0 mt-2">
                                <li>This will immediately change the employee's password</li>
                                <li>The employee will need to use the new password for their next login</li>
                                <li>This action will be logged for security purposes</li>
                                <li>Consider informing the employee about the password change</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#passwordResetForm">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-danger" id="resetSubmitBtn">
                                <i class="fas fa-key me-2"></i>Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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

<script>
// Toggle password visibility for reset form
function togglePasswordReset(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password matching validation for reset form
function checkPasswordMatchReset() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const confirmField = document.getElementById('confirm_password');
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            confirmField.classList.remove('is-invalid');
            confirmField.classList.add('is-valid');
        } else {
            confirmField.classList.remove('is-valid');
            confirmField.classList.add('is-invalid');
        }
    } else {
        confirmField.classList.remove('is-valid', 'is-invalid');
    }
}

// Event listeners for password reset form
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const resetForm = document.getElementById('resetPasswordForm');
    
    if (newPasswordField && confirmPasswordField) {
        newPasswordField.addEventListener('input', checkPasswordMatchReset);
        confirmPasswordField.addEventListener('input', checkPasswordMatchReset);
    }
    
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const adminPassword = document.getElementById('admin_password').value;
            
            if (!adminPassword) {
                e.preventDefault();
                alert('Admin password is required for authorization.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match. Please check and try again.');
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('New password must be at least 8 characters long.');
                return false;
            }
            
            if (!confirm('Are you sure you want to reset this employee\'s password? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
            
            // Disable submit button to prevent double submission
            document.getElementById('resetSubmitBtn').disabled = true;
            document.getElementById('resetSubmitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting Password...';
        });
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
