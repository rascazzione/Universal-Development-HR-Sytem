<?php
/**
 * Change Password Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/User.php';

// Require authentication
requireAuth();

$pageTitle = 'Change Password';
$pageHeader = true;
$pageDescription = 'Update your account password';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF protection
        protect_csrf();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword)) {
            throw new Exception('Current password is required');
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
        
        if ($currentPassword === $newPassword) {
            throw new Exception('New password must be different from current password');
        }
        
        // Change password
        $userClass = new User();
        $result = $userClass->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        
        if ($result) {
            setFlashMessage('Password changed successfully!', 'success');
            redirect('/dashboard.php');
        } else {
            throw new Exception('Failed to change password. Please try again.');
        }
        
    } catch (Exception $e) {
        setFlashMessage($e->getMessage(), 'error');
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Enter your current password to verify your identity.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                        <div class="password-strength mt-2" id="password-strength" style="display: none;">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" id="strength-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="strength-text"></small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="password-match-feedback">
                            Passwords do not match.
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Password Requirements:</strong>
                        <ul class="mb-0 mt-2">
                            <li>At least 8 characters long</li>
                            <li>Different from your current password</li>
                            <li>Consider using a mix of letters, numbers, and symbols</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Security Tips -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-shield-alt me-2"></i>Security Tips
                </h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Use a unique password that you don't use elsewhere</li>
                    <li>Consider using a password manager</li>
                    <li>Change your password regularly</li>
                    <li>Never share your password with anyone</li>
                    <li>Log out from shared computers</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
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

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = '';
    
    if (password.length >= 8) strength += 1;
    if (password.match(/[a-z]/)) strength += 1;
    if (password.match(/[A-Z]/)) strength += 1;
    if (password.match(/[0-9]/)) strength += 1;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
    
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const strengthContainer = document.getElementById('password-strength');
    
    if (password.length > 0) {
        strengthContainer.style.display = 'block';
        
        switch (strength) {
            case 0:
            case 1:
                strengthBar.style.width = '20%';
                strengthBar.className = 'progress-bar bg-danger';
                feedback = 'Very Weak';
                break;
            case 2:
                strengthBar.style.width = '40%';
                strengthBar.className = 'progress-bar bg-warning';
                feedback = 'Weak';
                break;
            case 3:
                strengthBar.style.width = '60%';
                strengthBar.className = 'progress-bar bg-info';
                feedback = 'Fair';
                break;
            case 4:
                strengthBar.style.width = '80%';
                strengthBar.className = 'progress-bar bg-primary';
                feedback = 'Good';
                break;
            case 5:
                strengthBar.style.width = '100%';
                strengthBar.className = 'progress-bar bg-success';
                feedback = 'Strong';
                break;
        }
        
        strengthText.textContent = feedback;
    } else {
        strengthContainer.style.display = 'none';
    }
}

// Password matching validation
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const confirmField = document.getElementById('confirm_password');
    const feedback = document.getElementById('password-match-feedback');
    
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

// Event listeners
document.getElementById('new_password').addEventListener('input', function() {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
});

document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match. Please check and try again.');
        return false;
    }
    
    if (newPassword.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return false;
    }
    
    // Disable submit button to prevent double submission
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing Password...';
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
