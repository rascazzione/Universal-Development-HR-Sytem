<?php
/**
 * Dashboard Page - Refactored with Legacy Bootstrap Design
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../classes/EvaluationWorkflow.php';
require_once __DIR__ . '/../classes/JobTemplate.php';
require_once __DIR__ . '/../includes/components/dashboard-widgets.php';
require_once __DIR__ . '/../includes/components/quick-actions.php';
require_once __DIR__ . '/../includes/components/dashboard-job-template.php';

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
$workflowClass = new EvaluationWorkflow();
$jobTemplateClass = new JobTemplate();

// Get dashboard data based on user role
$dashboardData = [];

// Prepare job template assignment info for the logged-in user
$jobTemplateAssignment = [
    'has_profile' => false,
    'has_template' => false,
    'status' => empty($_SESSION['employee_id']) ? 'missing_profile' : 'profile_not_found',
    'employee' => null,
    'template' => null,
    'counts' => [
        'kpis' => 0,
        'skills' => 0,
        'technical_skills' => 0,
        'soft_skills' => 0,
        'responsibilities' => 0,
        'values' => 0
    ],
    'details' => [
        'kpis' => [],
        'responsibilities' => [],
        'values' => [],
        'technical_skills' => [],
        'soft_skills' => [],
        'skills' => []
    ]
];

try {
    if (!empty($_SESSION['employee_id'])) {
        $employeeProfile = $employeeClass->getEmployeeById($_SESSION['employee_id'], true);
        if ($employeeProfile) {
            $jobTemplateAssignment['has_profile'] = true;
            $jobTemplateAssignment['employee'] = $employeeProfile;
            $jobTemplateAssignment['status'] = 'no_template';
            
            if (!empty($employeeProfile['job_template_id'])) {
                $templateSummary = $jobTemplateClass->getJobTemplateSummary($employeeProfile['job_template_id']);
                if ($templateSummary) {
                    $jobTemplateAssignment['has_template'] = true;
                    $jobTemplateAssignment['template'] = $templateSummary;
                    $jobTemplateAssignment['counts'] = [
                        'kpis' => (int) ($templateSummary['kpi_count'] ?? 0),
                        'skills' => (int) ($templateSummary['competency_count'] ?? 0),
                        'technical_skills' => (int) ($templateSummary['technical_skill_count'] ?? 0),
                        'soft_skills' => (int) ($templateSummary['soft_skill_count'] ?? 0),
                        'responsibilities' => (int) ($templateSummary['responsibility_count'] ?? 0),
                        'values' => (int) ($templateSummary['value_count'] ?? 0)
                    ];
                    $jobTemplateAssignment['status'] = 'ready';

                    $templateDetails = $jobTemplateClass->getCompleteJobTemplate($employeeProfile['job_template_id']);
                    if ($templateDetails) {
                        $jobTemplateAssignment['details'] = [
                            'kpis' => $templateDetails['kpis'] ?? [],
                            'responsibilities' => $templateDetails['responsibilities'] ?? [],
                            'values' => $templateDetails['values'] ?? [],
                            'technical_skills' => $templateDetails['technical_skills'] ?? [],
                            'soft_skills' => $templateDetails['soft_skills'] ?? [],
                            'skills' => $templateDetails['competencies'] ?? []
                        ];
                    } else {
                        $jobTemplateAssignment['status'] = 'summary_only';
                    }
                } else {
                    $jobTemplateAssignment['status'] = 'template_not_found';
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('Error loading dashboard job template assignment: ' . $e->getMessage());
    $jobTemplateAssignment['status'] = 'error';
}

if ($userRole === 'hr_admin') {
    // HR Admin Dashboard
    $dashboardData = [
        'total_employees' => $employeeClass->getEmployeeStats()['total_employees'],
        'total_evaluations' => $evaluationClass->getEvaluationStats()['total_evaluations'],
        'pending_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['draft'] ?? 0,
        'submitted_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['submitted'] ?? 0,
        'approved_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['approved'] ?? 0,
        'rejected_evaluations' => $evaluationClass->getEvaluationStats()['by_status']['rejected'] ?? 0,
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
        'submitted_evaluations' => count(array_filter($teamEvaluations, fn($e) => $e['status'] === 'submitted')),
        'approved_evaluations' => count(array_filter($teamEvaluations, fn($e) => $e['status'] === 'approved')),
        'rejected_evaluations' => count(array_filter($teamEvaluations, fn($e) => $e['status'] === 'rejected')),
        'team_members' => $teamMembers,
        'recent_evaluations' => array_slice($teamEvaluations, 0, 5),
        'current_period' => $periodClass->getCurrentPeriod()
    ];
    
    // LOG: Manager dashboard data
    error_log("MANAGER_DASHBOARD - Manager ID: $managerId, Team Evaluations: " . count($teamEvaluations));
} else {
    // Employee Dashboard
    $employeeEvaluations = $evaluationClass->getEmployeeEvaluations($_SESSION['employee_id']);

    // Get workflow status for active periods
    $workflowStatus = [];
    $activePeriods = $periodClass->getActivePeriods();
    foreach ($activePeriods as $period) {
        $workflowStatus[$period['period_id']] = $workflowClass->getWorkflowStatus($_SESSION['employee_id'], $period['period_id']);
    }

    $dashboardData = [
        'total_evaluations' => count($employeeEvaluations),
        'latest_evaluation' => $employeeEvaluations[0] ?? null,
        'evaluation_history' => array_slice($employeeEvaluations, 0, 5),
        'current_period' => $periodClass->getCurrentPeriod(),
        'employee_info' => $employeeClass->getEmployeeById($_SESSION['employee_id']),
        'workflow_status' => $workflowStatus,
        'active_periods' => $activePeriods
    ];
}

// Add custom CSS and JS for job template component
$pageStylesheets = [
    '/assets/css/dashboard-job-template.css'
];

$pageScripts = [
    '/assets/js/dashboard-job-template.js'
];

include __DIR__ . '/../templates/header.php';
?>

<!-- Welcome Section - Bootstrap Design -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body py-4">
                    <h1 class="h3 mb-2">Welcome back, <?php echo htmlspecialchars(getUserDisplayName()); ?>!</h1>
                    <p class="text-muted mb-0">
                        <?php echo getUserRoleDisplayName(); ?> • <?php echo formatDate(date('Y-m-d'), 'l, F j, Y'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Template Accordion Section -->
    <div class="row mb-4">
        <div class="col-12">
            <?php echo renderDashboardJobTemplate(
                $jobTemplateAssignment,
                $jobTemplateAssignment['employee']['employee_id'] ?? null,
                false // collapsed by default
            ); ?>
        </div>
    </div>
</div>

<?php if ($jobTemplateAssignment['has_template'] && $jobTemplateAssignment['status'] === 'summary_only'): ?>
<div class="container-fluid">
    <div class="alert alert-warning">
        <strong>Heads up:</strong> We could load your template summary but not the detailed components. Refresh the page or contact HR if the issue persists.
    </div>
</div>
<?php endif; ?>

<?php if ($userRole === 'hr_admin' || $userRole === 'manager'): ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-3 mb-md-0">
                        <h5 class="mb-1"><i class="fas fa-arrow-up text-danger me-2"></i>Upward Feedback Hub</h5>
                        <p class="text-muted mb-0">
                            Launch a dedicated view to monitor submissions, invite team responses, and review insights from your reports.
                        </p>
                    </div>
                    <a href="/upward-feedback/dashboard.php" class="btn btn-danger">
                        <i class="fas fa-external-link-alt me-2"></i>Open Upward Feedback
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dashboard Widgets - Using Reusable Components -->
<div class="container-fluid">
    <?php echo renderDashboardWidgets($userRole, $dashboardData); ?>
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
    
    <!-- Workflow Status Section for Employees -->
    <?php if ($userRole === 'employee' && !empty($dashboardData['active_periods'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Evaluation Workflow Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($dashboardData['active_periods'] as $period): ?>
                        <?php
                        $workflow = $dashboardData['workflow_status'][$period['period_id']] ?? [];
                        $hasSelf = $workflow['has_self'] ?? false;
                        $selfState = $workflow['self_state'] ?? 'pending_self';
                        $hasFinal = $workflow['has_final'] ?? false;
                        $finalState = $workflow['final_state'] ?? 'pending_self';
                        ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong><?php echo htmlspecialchars($period['period_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?>
                                </small>
                            </div>
                            <div class="col-md-8">
                                <?php if (!$hasSelf): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Self-evaluation period is active. Please complete your self-assessment.
                                        <a href="/evaluation/self-evaluation.php" class="btn btn-sm btn-primary ms-2">Start Self-Evaluation</a>
                                    </div>
                                <?php elseif ($hasSelf && $selfState === 'pending_self'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Action Required:</strong> Complete your self-evaluation
                                        <a href="/evaluation/self-evaluation.php" class="btn btn-sm btn-warning ms-2">Complete Now</a>
                                    </div>
                                <?php elseif ($hasSelf && $selfState === 'self_submitted'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-clock me-2"></i>
                                        Self-evaluation submitted. Waiting for manager review.
                                    </div>
                                <?php elseif ($hasFinal && $finalState === 'final_delivered'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Your final evaluation is ready to view.
                                        <a href="/evaluation/list.php" class="btn btn-sm btn-success ms-2">View Evaluation</a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-spinner me-2"></i>
                                        Evaluation in progress...
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions / Current Period - Using Reusable Component -->
    <?php echo renderQuickActions($userRole, $jobTemplateAssignment); ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
