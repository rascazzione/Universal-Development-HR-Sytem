<?php
/**
 * Self-Assessment dashboard placeholder
 * Legacy module removed until future development
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

$pageTitle = 'Self-Assessment';
$pageHeader = true;
$pageDescription = 'This feature is no longer available.';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-ban fa-3x text-muted"></i>
                    </div>
                    <h2 class="h4 mb-3">Self-Assessment Disabled</h2>
                    <p class="text-muted mb-4">
                        The self-assessment experience is not part of this release. Reach out to your HR administrator
                        if you believe you should still have access.
                    </p>
                    <a href="/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
