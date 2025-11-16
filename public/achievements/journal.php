<?php
/**
 * Achievement journal placeholder
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

$pageTitle = 'Achievement Journal';
$pageHeader = true;
$pageDescription = 'This feature has been removed.';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-info-circle fa-3x text-muted"></i>
                    </div>
                    <h2 class="h4 mb-3">Achievement Journal Unavailable</h2>
                    <p class="text-muted mb-4">
                        The achievement journal is not part of the current scope. Please capture performance notes
                        directly within evaluations until a future update restores this capability.
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
