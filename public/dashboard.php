<?php
/**
 * Dashboard Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/EvaluationPeriod.php';

// Require authentication
requireAuth();

$pageTitle = 'Dashboard';
$pageHeader = true;
$pageDescription = 'Welcome to your performance evaluation dashboard';

// Get current user and employee info
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'];

// Initialize classes
$employeeClass = new Employee();
$evaluationClass = new Evaluation();
$periodClass = new EvaluationPeriod();

// Get dashboard data based on user role
$dashboardData = [];

if ($userRole === 'hr_admin') {
    // HR Admin Dashboard
    $dashboardData = [
        'total_employees' => $employeeClass->getEmployeeStats()['total_employees'],
        'total_evaluations' => $evaluationClass->getEvaluationStats()['total_evaluations'],
        'pending_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['draft'] ?? 0,
        'completed_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['approved'] ?? 0,
        'active_periods' => count($periodClass->getActivePeriods()),
        'recent_evaluations' => $evaluationClass->getEvaluations(1, 5)['evaluations'],
        'evaluation_stats' => $evaluationClass->getEvaluationStats(),
        'employee_stats' => $employeeClass->getEmployeeStats()
    ];
} elseif ($userRole === 'manager') {
    // Manager Dashboard - ENHANCED with direct manager relationship
    $managerId = $_SESSION['employee_id'];
    
    // Use new direct manager evaluation query
    $teamEvaluations = $evaluationClass->getManagerEvaluations($managerId);
    
    // Get team members for additional context
    $teamMembers = $employeeClass->getTeamMembers($managerId);
    
    $dashboardData = [
        'team_size' => count($teamMembers),
        'team_evaluations' => count($teamEvaluations),
        'pending_evaluations' => count(array_filter($teamEvaluations, fn($e) => $e['status'] === 'draft')),
        'completed_evaluations' => count(array_filter($teamEvaluations, fn($e) => $e['status'] === 'approved')),
        'team_members' => $teamMembers,
        'recent_evaluations' => array_slice($teamEvaluations, 0, 5),
        'current_period' => $periodClass->getCurrentPeriod()
    ];
    
    // LOG: Manager dashboard data
    error_log("MANAGER_DASHBOARD - Manager ID: $managerId, Team Evaluations: " . count($teamEvaluations));
} else {
    // Employee Dashboard
    $employeeEvaluations = $evaluationClass->getEmployeeEvaluations($_SESSION['employee_id']);
    
    $dashboardData = [
        'total_evaluations' => count($employeeEvaluations),
        'latest_evaluation' => $employeeEvaluations[0] ?? null,
        'evaluation_history' => array_slice($employeeEvaluations, 0, 5),
        'current_period' => $periodClass->getCurrentPeriod(),
        'employee_info' => $employeeClass->getEmployeeById($_SESSION['employee_id'])
    ];
}

include __DIR__ . '/../templates/header.php';
?>

<div class="row">
    <!-- Welcome Section -->
    <div class="col-12 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-1">
                            Welcome back, <?php echo htmlspecialchars(getUserDisplayName()); ?>!
                        </h4>
                        <p class="card-text mb-0">
                            <?php echo getUserRoleDisplayName(); ?> • 
                            <?php echo formatDate(date('Y-m-d'), 'l, F j, Y'); ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-circle fa-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Dashboard Widgets -->
    <?php if ($userRole === 'hr_admin'): ?>
    <!-- HR Admin Widgets -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-title">Total Employees</div>
            <div class="widget-value"><?php echo number_format($dashboardData['total_employees']); ?></div>
            <div class="widget-change">
                <a href="/employees/list.php" class="text-decoration-none">View all employees</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon success">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="widget-title">Total Evaluations</div>
            <div class="widget-value"><?php echo number_format($dashboardData['total_evaluations']); ?></div>
            <div class="widget-change">
                <a href="/evaluation/list.php" class="text-decoration-none">View all evaluations</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="widget-title">Pending Evaluations</div>
            <div class="widget-value"><?php echo number_format($dashboardData['pending_evaluations']); ?></div>
            <div class="widget-change">
                <a href="/evaluation/list.php?status=draft" class="text-decoration-none">View pending</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon info">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="widget-title">Active Periods</div>
            <div class="widget-value"><?php echo number_format($dashboardData['active_periods']); ?></div>
            <div class="widget-change">
                <a href="/admin/periods.php" class="text-decoration-none">Manage periods</a>
            </div>
        </div>
    </div>
    
    <?php elseif ($userRole === 'manager'): ?>
    <!-- Manager Widgets -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-title">Team Members</div>
            <div class="widget-value"><?php echo number_format($dashboardData['team_size']); ?></div>
            <div class="widget-change">
                <a href="/employees/team.php" class="text-decoration-none">View team</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon success">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="widget-title">Team Evaluations</div>
            <div class="widget-value"><?php echo number_format($dashboardData['team_evaluations']); ?></div>
            <div class="widget-change">
                <a href="/evaluation/my-evaluations.php" class="text-decoration-none">View evaluations</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="widget-title">Pending</div>
            <div class="widget-value"><?php echo number_format($dashboardData['pending_evaluations']); ?></div>
            <div class="widget-change">
                <a href="/evaluation/create.php" class="text-decoration-none">Create evaluation</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon info">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="widget-title">Completed</div>
            <div class="widget-value"><?php echo number_format($dashboardData['completed_evaluations']); ?></div>
            <div class="widget-change">
                <span class="text-success">This period</span>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Employee Widgets -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon primary">
                <i class="fas fa-star"></i>
            </div>
            <div class="widget-title">Latest Rating</div>
            <div class="widget-value">
                <?php if ($dashboardData['latest_evaluation']): ?>
                    <?php
                    $rating = $dashboardData['latest_evaluation']['overall_rating'] ?? null;
                    if ($rating !== null && is_numeric($rating)) {
                        echo number_format($rating, 1);
                    } else {
                        echo "Pending";
                    }
                    ?>/5.0
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </div>
            <div class="widget-change">
                <?php if ($dashboardData['latest_evaluation']): ?>
                    <span class="text-muted"><?php echo formatDate($dashboardData['latest_evaluation']['created_at'], 'M Y'); ?></span>
                <?php else: ?>
                    <span class="text-muted">No evaluations yet</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon success">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="widget-title">Total Evaluations</div>
            <div class="widget-value"><?php echo number_format($dashboardData['total_evaluations']); ?></div>
            <div class="widget-change">
                <a href="/evaluation/my-evaluations.php" class="text-decoration-none">View history</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="widget-icon info">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="widget-title">Current Period</div>
            <div class="widget-value">
                <?php if ($dashboardData['current_period']): ?>
                    Active
                <?php else: ?>
                    None
                <?php endif; ?>
            </div>
            <div class="widget-change">
                <?php if ($dashboardData['current_period']): ?>
                    <span class="text-muted"><?php echo htmlspecialchars($dashboardData['current_period']['period_name']); ?></span>
                <?php else: ?>
                    <span class="text-muted">No active period</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Recent Activity / Evaluations -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    <?php if ($userRole === 'employee'): ?>
                        My Recent Evaluations
                    <?php else: ?>
                        Recent Evaluations
                    <?php endif; ?>
                </h5>
                <a href="<?php echo $userRole === 'employee' ? '/evaluation/my-evaluations.php' : '/evaluation/list.php'; ?>" 
                   class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php 
                $recentEvaluations = $userRole === 'employee' 
                    ? $dashboardData['evaluation_history'] 
                    : $dashboardData['recent_evaluations'];
                
                if (empty($recentEvaluations)): 
                ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No evaluations found.</p>
                    <?php if ($userRole !== 'employee'): ?>
                    <a href="/evaluation/create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create First Evaluation
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <?php if ($userRole !== 'employee'): ?>
                                <th>Employee</th>
                                <?php endif; ?>
                                <th>Period</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvaluations as $evaluation): ?>
                            <tr>
                                <?php if ($userRole !== 'employee'): ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($evaluation['position'] ?? ''); ?></small>
                                </td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($evaluation['period_name']); ?></td>
                                <td>
                                    <?php if ($evaluation['overall_rating']): ?>
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star rating-star <?php echo $i <= $evaluation['overall_rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2"><?php echo number_format($evaluation['overall_rating'], 1); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not rated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge status-badge status-<?php echo $evaluation['status']; ?>">
                                        <?php echo ucfirst($evaluation['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($evaluation['created_at']); ?></td>
                                <td>
                                    <a href="/evaluation/view.php?id=<?php echo $evaluation['evaluation_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (canEditEvaluation($evaluation)): ?>
                                    <a href="/evaluation/edit.php?id=<?php echo $evaluation['evaluation_id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions / Current Period -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($userRole === 'hr_admin'): ?>
                    <a href="/evaluation/create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Evaluation
                    </a>
                    <a href="/employees/create.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i>Add Employee
                    </a>
                    <hr class="my-2">
                    <small class="text-muted mb-2 d-block">System Configuration</small>
                    <a href="/admin/job_templates.php" class="btn btn-outline-secondary btn-sm mb-1">
                        <i class="fas fa-briefcase me-2"></i>Job Templates
                    </a>
                    <a href="/admin/kpis.php" class="btn btn-outline-success btn-sm mb-1">
                        <i class="fas fa-chart-line me-2"></i>Company KPIs
                    </a>
                    <a href="/admin/competencies.php" class="btn btn-outline-info btn-sm mb-1">
                        <i class="fas fa-brain me-2"></i>Competencies
                    </a>
                    <a href="/admin/values.php" class="btn btn-outline-danger btn-sm mb-1">
                        <i class="fas fa-heart me-2"></i>Company Values
                    </a>
                    <a href="/admin/periods.php" class="btn btn-outline-warning btn-sm mb-1">
                        <i class="fas fa-calendar-plus me-2"></i>Evaluation Periods
                    </a>
                    <hr class="my-2">
                    <a href="/reports/performance.php" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar me-2"></i>View Reports
                    </a>
                    <?php elseif ($userRole === 'manager'): ?>
                    <a href="/evaluation/create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Evaluation
                    </a>
                    <a href="/employees/team.php" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>View Team
                    </a>
                    <a href="/evaluation/my-evaluations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-clipboard-list me-2"></i>My Evaluations
                    </a>
                    <?php else: ?>
                    <a href="/evaluation/my-evaluations.php" class="btn btn-primary">
                        <i class="fas fa-clipboard-list me-2"></i>My Evaluations
                    </a>
                    <?php if (!empty($_SESSION['employee_id'])): ?>
                    <a href="/employees/edit.php?id=<?php echo $_SESSION['employee_id']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                    <?php else: ?>
                    <a href="#" onclick="alert('Profile functionality requires employee account setup. Contact HR.');" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                    <?php endif; ?>
                    </a>
                    <a href="/change-password.php" class="btn btn-outline-secondary">
                        <i class="fas fa-key me-2"></i>Change Password
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Current Period Info -->
                <?php if (isset($dashboardData['current_period']) && $dashboardData['current_period']): ?>
                <hr>
                <div class="current-period">
                    <h6 class="mb-2">
                        <i class="fas fa-calendar-check me-2"></i>Current Period
                    </h6>
                    <p class="mb-1">
                        <strong><?php echo htmlspecialchars($dashboardData['current_period']['period_name']); ?></strong>
                    </p>
                    <p class="text-muted small mb-0">
                        <?php echo formatDate($dashboardData['current_period']['start_date']); ?> - 
                        <?php echo formatDate($dashboardData['current_period']['end_date']); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>