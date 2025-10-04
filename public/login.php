<?php
/**
 * Login Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isAuthenticated()) {
    redirect('/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    protect_csrf();
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        $user = new User();
        $result = $user->login($username, $password);
        
        if (is_array($result) && isset($result['error'])) {
            $error = $result['error'];
        } elseif ($result) {
            // Successful login - redirect to intended page or dashboard
            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectUrl);
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$pageTitle = 'Login - ' . getAppSetting('system_name', APP_NAME);
$bodyClass = 'login-page';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="<?php echo $bodyClass; ?>">
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2 class="mb-0"><?php echo getAppSetting('system_name', APP_NAME); ?></h2>
                <p class="text-muted mb-0">Performance Evaluation System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i>Username or Email
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($username); ?>"
                               placeholder="Enter your username or email"
                               required 
                               autofocus
                               autocomplete="username">
                        <div class="invalid-feedback">
                            Please enter your username or email address.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required
                               autocomplete="current-password">
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="/forgot-password.php" class="text-decoration-none">
                        <i class="fas fa-question-circle me-1"></i>Forgot your password?
                    </a>
                </div>
                
                <?php if (getAppSetting('allow_registration', false)): ?>
                <div class="text-center mt-3">
                    <a href="/register.php" class="text-decoration-none">
                        <i class="fas fa-user-plus me-1"></i>Create an account
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Demo Credentials (Remove in production) -->
    <div class="position-fixed bottom-0 start-0 m-3">
        <div class="card" style="max-width: 300px;">
            <div class="card-header bg-info text-white">
                <small><i class="fas fa-info-circle me-1"></i>Demo Credentials</small>
            </div>
            <div class="card-body p-2">
                <small>
                    <strong>Admin:</strong> admin / admin123<br>
                    <strong>Manager:</strong> manager / manager123<br>
                    <strong>Employee:</strong> employee / employee123
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation -->
    <script>
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    </script>
    
</body>
</html>
