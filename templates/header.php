<?php
require_once __DIR__ . '/../includes/auth.php';

// Get page title from variable or use default
$pageTitle = $pageTitle ?? 'Performance Evaluation System';
$bodyClass = $bodyClass ?? '';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <?php if (!empty($pageStylesheets) && is_array($pageStylesheets)): ?>
        <?php foreach ($pageStylesheets as $stylesheet): ?>
            <link href="<?php echo htmlspecialchars($stylesheet); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom styles for this page -->
    <?php if (isset($customCSS)): ?>
        <style><?php echo $customCSS; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo $bodyClass; ?>">
    
    <?php if (isAuthenticated()): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>
                <?php echo getAppSetting('system_name', APP_NAME); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php
                    $menuItems = getNavigationMenu();
                    foreach ($menuItems as $item):
                    ?>
                    <li class="nav-item <?php echo isset($item['submenu']) ? 'dropdown' : ''; ?>">
                        <?php if (isset($item['submenu'])): ?>
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="<?php echo $item['icon']; ?> me-1"></i>
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($item['submenu'] as $subItem): ?>
                            <li><a class="dropdown-item" href="<?php echo $subItem['url']; ?>">
                                <?php echo htmlspecialchars($subItem['title']); ?>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <a class="nav-link" href="<?php echo $item['url']; ?>">
                            <i class="<?php echo $item['icon']; ?> me-1"></i>
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars(getUserDisplayName()); ?>
                            <small class="text-light opacity-75 ms-1">(<?php echo getUserRoleDisplayName(); ?>)</small>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (!empty($_SESSION['employee_id'])): ?>
                            <li><a class="dropdown-item" href="/employees/view.php?id=<?php echo $_SESSION['employee_id']; ?>">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/employees/edit.php?id=<?php echo $_SESSION['employee_id']; ?>">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a></li>
                            <?php else: ?>
                            <li><a class="dropdown-item" href="#" onclick="alert('Profile functionality requires employee account setup. Contact HR.');">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/change-password.php">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?php echo isAuthenticated() ? 'container-fluid mt-4' : ''; ?>">
        
        <!-- Flash Messages -->
        <?php
        $flashMessages = getFlashMessages();
        foreach ($flashMessages as $message):
        ?>
        <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
        
        <!-- Page Header -->
        <?php if (isset($pageHeader) && $pageHeader): ?>
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        <?php if (isset($pageDescription)): ?>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($pageDescription); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($pageActions)): ?>
                    <div class="btn-toolbar" role="toolbar">
                        <?php echo $pageActions; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
