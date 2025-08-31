<?php
/**
 * 360-Degree Features Quick Access Navigation
 * Central hub for all 360-degree feedback features
 */

require_once __DIR__ . '/../../includes/auth.php';

// Require authentication
requireAuth();

$pageTitle = '360° Feedback Features';
$pageHeader = true;
$pageDescription = 'Complete access to all 360-degree feedback and performance management features';

// Get current user info
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'];
$employeeId = $_SESSION['employee_id'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="text-center mb-4">
                <h1 class="h2 mb-2">
                    <i class="fas fa-sync-alt me-2 text-primary"></i>
                    360° Feedback Features
                </h1>
                <p class="text-muted">Comprehensive performance management and development tools</p>
            </div>
        </div>
    </div>

    <!-- Feature Overview Cards -->
    <div class="row mb-4">
        <!-- Self-Assessment Card -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-file-alt fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Self-Assessment</h5>
                    <p class="card-text">
                        Reflect on your performance, gather evidence, and evaluate yourself against key competencies.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/self-assessment/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right me-1"></i>Start Assessment
                    </a>
                </div>
            </div>
        </div>

        <!-- Achievement Journal Card -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-trophy fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Achievement Journal</h5>
                    <p class="card-text">
                        Document your achievements, successes, and growth moments throughout the evaluation period.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/achievements/journal.php" class="btn btn-success">
                        <i class="fas fa-arrow-right me-1"></i>View Journal
                    </a>
                </div>
            </div>
        </div>

        <!-- KUDOS Recognition Card -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-gift fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title">KUDOS Recognition</h5>
                    <p class="card-text">
                        Give and receive recognition for outstanding performance and positive contributions.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/kudos/feed.php" class="btn btn-warning">
                        <i class="fas fa-arrow-right me-1"></i>KUDOS Feed
                    </a>
                </div>
            </div>
        </div>

        <!-- OKR Management Card -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-bullseye fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">OKR Management</h5>
                    <p class="card-text">
                        Set, track, and achieve Objectives and Key Results aligned with organizational goals.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/okr/dashboard.php" class="btn btn-info">
                        <i class="fas fa-arrow-right me-1"></i>Manage OKRs
                    </a>
                </div>
            </div>
        </div>

        <!-- Development Plans Card -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-graduation-cap fa-3x text-secondary"></i>
                    </div>
                    <h5 class="card-title">Development Plans</h5>
                    <p class="card-text">
                        Create and track Individual Development Plans (IDP) for career growth and skill enhancement.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/idp/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-1"></i>View IDP
                    </a>
                </div>
            </div>
        </div>

        <!-- Upward Feedback Card (Managers Only) -->
        <?php if ($userRole === 'hr_admin' || $userRole === 'manager'): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-arrow-up fa-3x text-danger"></i>
                    </div>
                    <h5 class="card-title">Upward Feedback</h5>
                    <p class="card-text">
                        Receive anonymous feedback from your team members to improve leadership effectiveness.
                    </p>
                    <div class="feature-status mb-3">
                        <span class="badge bg-secondary">Ready to use</span>
                    </div>
                    <a href="/upward-feedback/dashboard.php" class="btn btn-danger">
                        <i class="fas fa-arrow-right me-1"></i>View Feedback
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Start Guide -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-compass me-2"></i>
                        Quick Start Guide
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">1</div>
                                <h6>Start with Self-Assessment</h6>
                                <p class="text-muted small">Begin by evaluating your performance and gathering evidence of your achievements.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">2</div>
                                <h6>Document Achievements</h6>
                                <p class="text-muted small">Keep a running journal of your successes and growth moments throughout the period.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">3</div>
                                <h6>Set Development Goals</h6>
                                <p class="text-muted small">Create OKRs and development plans to align your growth with organizational objectives.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-Specific Information -->
    <div class="row mb-4">
        <?php if ($userRole === 'employee'): ?>
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Employee Features
                    </h5>
                </div>
                <div class="card-body">
                    <p>As an employee, you have access to all the tools needed for your development and performance tracking:</p>
                    <ul>
                        <li><strong>Self-Assessment:</strong> Regular self-evaluation and evidence gathering</li>
                        <li><strong>Achievement Journal:</strong> Document your successes and growth moments</li>
                        <li><strong>KUDOS Recognition:</strong> Give and receive recognition from peers</li>
                        <li><strong>OKR Management:</strong> Set and track your objectives and key results</li>
                        <li><strong>Development Plans:</strong> Create your individual development plan</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php elseif ($userRole === 'manager'): ?>
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Manager Features
                    </h5>
                </div>
                <div class="card-body">
                    <p>As a manager, you have access to additional tools to support your team's development:</p>
                    <ul>
                        <li><strong>All Employee Features:</strong> Full access to personal development tools</li>
                        <li><strong>Team Analytics:</strong> View team performance and development insights</li>
                        <li><strong>Upward Feedback:</strong> Receive anonymous feedback from your team</li>
                        <li><strong>Team OKRs:</strong> Set and track team-level objectives</li>
                        <li><strong>Coaching Tools:</strong> Support your team members' growth plans</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php elseif ($userRole === 'hr_admin'): ?>
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        HR Admin Features
                    </h5>
                </div>
                <div class="card-body">
                    <p>As an HR Admin, you have access to administrative tools and system-wide analytics:</p>
                    <ul>
                        <li><strong>All User Features:</strong> Complete access to all 360° functionality</li>
                        <li><strong>System Configuration:</strong> Set up KUDOS categories and feedback settings</li>
                        <li><strong>360° Analytics:</strong> Comprehensive reporting and insights</li>
                        <li><strong>Admin Dashboard:</strong> Monitor system usage and adoption</li>
                        <li><strong>Upward Feedback Management:</strong> Configure and manage feedback processes</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.feature-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: 1px solid #e9ecef;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,0.05);
}

.step-card {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.step-number {
    width: 40px;
    height: 40px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #007bff;
    color: white;
    font-weight: bold;
    font-size: 18px;
}

@media (max-width: 768px) {
    .feature-card {
        margin-bottom: 1.5rem;
    }
    
    .step-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include __DIR__ . '/../../templates/footer.php'; ?>