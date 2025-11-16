<?php
/**
 * OKR dashboard placeholder
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

$pageTitle = 'OKR Management';
$pageHeader = true;
$pageDescription = 'Objective tracking is not enabled.';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-bullseye fa-3x text-muted"></i>
                    </div>
                    <h2 class="h4 mb-3">OKR Module Disabled</h2>
                    <p class="text-muted mb-4">
                        Objective and Key Result tracking will return in a future iteration. Continue managing goals
                        with your current tooling for now.
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
