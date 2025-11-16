<?php
/**
 * Kudos feed placeholder
 */

require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

$pageTitle = 'Kudos Feed';
$pageHeader = true;
$pageDescription = 'Recognition is currently handled outside the platform.';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-gift fa-3x text-muted"></i>
                    </div>
                    <h2 class="h4 mb-3">Kudos Feed Disabled</h2>
                    <p class="text-muted mb-4">
                        Peer recognition features are not available right now. Please use your existing channels to
                        celebrate great work until the in-app feed returns.
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
