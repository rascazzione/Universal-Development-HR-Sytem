<?php
/**
 * Pending to Build placeholder
 */

require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$featureName = trim($_GET['feature'] ?? '');
if ($featureName === '') {
    $featureName = 'This feature';
}

$pageTitle = 'Pending to Build';
$pageHeader = true;
$pageDescription = sprintf('%s is currently under construction.', $featureName);

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-hard-hat fa-3x text-warning"></i>
                    </div>
                    <h1 class="h4 mb-3">Pending to Build</h1>
                    <p class="text-muted mb-4">
                        <?php echo htmlspecialchars($featureName); ?> is not available yet. Our team is still building this experience.
                    </p>
                    <a href="/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
