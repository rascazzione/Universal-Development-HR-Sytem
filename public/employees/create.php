<?php
/**
 * Employee Create Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';

// Require HR Admin access
requireAuth();
if (!isHRAdmin()) {
    redirect('/dashboard.php');
}

$pageTitle = 'Add New Employee';
$pageHeader = true;
$pageDescription = 'Create a new employee record';

// Initialize classes
$employeeClass = new Employee();
$userClass = new User();
$jobTemplateClass = new JobTemplate();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create user account first if email is provided
        $userId = null;
        if (!empty($_POST['email']) && !empty($_POST['username'])) {
            $password = !empty($_POST['password']) ? $_POST['password'] : 'employee123';
            
            $userData = [
                'username' => trim($_POST['username']),
                'email' => trim($_POST['email']),
                'password' => $password,
                'role' => $_POST['role'] ?? 'employee'
            ];
            
            $userId = $userClass->createUser($userData);
            if (!$userId) {
                throw new Exception('Failed to create user account');
            }
        }

        // Create employee record
        $employeeData = [
            'user_id' => $userId, // This can be null if no user account is created
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'position' => trim($_POST['position']) ?: null,
            'department' => trim($_POST['department']) ?: null,
            'manager_id' => $_POST['manager_id'] ?: null,
            'hire_date' => $_POST['hire_date'] ?: null,
            'phone' => trim($_POST['phone']) ?: null,
            'address' => trim($_POST['address']) ?: null,
            'job_template_id' => $_POST['job_template_id'] ?: null
        ];

        $employeeId = $employeeClass->createEmployee($employeeData);
        
        if ($employeeId) {
            $message = 'Employee created successfully!';
            if ($userId) {
                $message .= ' User account also created.';
            }
            setFlashMessage($message, 'success');
            redirect('/employees/view.php?id=' . $employeeId);
        } else {
            setFlashMessage('Failed to create employee. Please try again.', 'error');
        }
    } catch (Exception $e) {
        error_log('Employee creation error: ' . $e->getMessage());
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Get potential managers
$potentialManagers = $employeeClass->getPotentialManagers();

// Get departments and job templates
$departments = $employeeClass->getDepartments();
$jobTemplates = $jobTemplateClass->getJobTemplates();

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add New Employee</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <!-- Personal Information -->
                    <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a last name.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" 
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
                                            <?php echo ($_POST['manager_id'] ?? '') == $manager['employee_id'] ? 'selected' : ''; ?>>
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
                                       value="<?php echo $_POST['hire_date'] ?? ''; ?>">
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
                                            <?php echo ($_POST['job_template_id'] ?? '') == $template['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($template['position_title']); ?>
                                        <?php if ($template['department']): ?>
                                            - <?php echo htmlspecialchars($template['department']); ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Assign a job template to define evaluation criteria for this employee.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- User Account Information -->
                    <h6 class="border-bottom pb-2 mb-3 mt-4">User Account (Optional)</h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Create a user account to allow this employee to log into the system.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                       id="username" name="username"
                                       value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div>
                                <?php else: ?>
                                    <div class="form-text">Leave blank if no system access needed</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                       id="email" name="email"
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php else: ?>
                                    <div class="form-text">Required for system access</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Leave blank for default: employee123">
                                <div class="form-text">Default password: employee123</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="employee" <?php echo ($formData['role'] ?? 'employee') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                    <option value="manager" <?php echo ($formData['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="hr_admin" <?php echo ($formData['role'] ?? '') === 'hr_admin' ? 'selected' : ''; ?>>HR Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/employees/list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <div>
                            <a href="/employees/list.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate username from first and last name
document.addEventListener('DOMContentLoaded', function() {
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    
    function generateUsername() {
        const firstName = firstNameInput.value.trim().toLowerCase();
        const lastName = lastNameInput.value.trim().toLowerCase();
        
        if (firstName && lastName && !usernameInput.value) {
            usernameInput.value = firstName + '.' + lastName;
        }
        
        if (firstName && lastName && !emailInput.value) {
            emailInput.value = firstName + '.' + lastName + '@company.com';
        }
    }
    
    firstNameInput.addEventListener('blur', generateUsername);
    lastNameInput.addEventListener('blur', generateUsername);
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>