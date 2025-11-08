<?php
/**
 * Dashboard Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../classes/JobTemplate.php';

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
    <div class="col-12 mb-4">
        <div class="card job-template-hero <?php echo $jobTemplateAssignment['has_template'] ? '' : 'border-warning'; ?>">
            <div class="card-body">
                <?php if ($jobTemplateAssignment['has_template']): ?>
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start gap-4">
                        <div class="flex-grow-1">
                            <span class="role-blueprint-label">Role Blueprint</span>
                            <h3 class="mb-1"><?php echo htmlspecialchars($jobTemplateAssignment['template']['position_title']); ?></h3>
                            <?php if (!empty($jobTemplateAssignment['template']['department'])): ?>
                            <p class="text-muted mb-2">
                                <?php echo htmlspecialchars($jobTemplateAssignment['template']['department']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($jobTemplateAssignment['template']['description'])): ?>
                                <?php
                                $jobTemplateDescription = $jobTemplateAssignment['template']['description'];
                                if (strlen($jobTemplateDescription) > 220) {
                                    $jobTemplateDescription = substr($jobTemplateDescription, 0, 217) . '...';
                                }
                                ?>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($jobTemplateDescription); ?></p>
                            <?php else: ?>
                                <p class="mb-0 text-muted">
                                    These are the KPIs, responsibilities, skills, and values you are expected to live every day.
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="job-template-stats d-flex flex-wrap justify-content-lg-end gap-3">
                            <?php
                            $heroStats = [
                                [
                                    'label' => 'KPIs',
                                    'value' => number_format($jobTemplateAssignment['counts']['kpis']),
                                    'icon' => 'fas fa-bullseye',
                                    'accent' => 'primary',
                                    'sub' => 'Key indicators'
                                ],
                                [
                                    'label' => 'Responsibilities',
                                    'value' => number_format($jobTemplateAssignment['counts']['responsibilities']),
                                    'icon' => 'fas fa-list-check',
                                    'accent' => 'success',
                                    'sub' => 'What you own'
                                ],
                                [
                                    'label' => 'Skills',
                                    'value' => number_format($jobTemplateAssignment['counts']['skills']),
                                    'icon' => 'fas fa-layer-group',
                                    'accent' => 'info',
                                    'sub' => 'Competencies tracked'
                                ],
                                [
                                    'label' => 'Values',
                                    'value' => number_format($jobTemplateAssignment['counts']['values']),
                                    'icon' => 'fas fa-heart',
                                    'accent' => 'danger',
                                    'sub' => 'Cultural anchors'
                                ],
                                [
                                    'label' => 'Tech vs Soft',
                                    'value' => number_format($jobTemplateAssignment['counts']['technical_skills']) . ' / ' . number_format($jobTemplateAssignment['counts']['soft_skills']),
                                    'icon' => 'fas fa-code-branch',
                                    'accent' => 'dark',
                                    'sub' => 'Technical / Soft'
                                ],
                            ];
                            foreach ($heroStats as $stat):
                            ?>
                            <div class="stat-pill" data-accent="<?php echo $stat['accent']; ?>">
                                <div class="stat-icon">
                                    <i class="<?php echo $stat['icon']; ?>"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
                                    <div class="stat-value"><?php echo htmlspecialchars($stat['value']); ?></div>
                                    <?php if (!empty($stat['sub'])): ?>
                                    <div class="stat-sub text-muted"><?php echo htmlspecialchars($stat['sub']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if (!empty($jobTemplateAssignment['employee']['employee_id'])): ?>
                        <a href="/employees/view.php?id=<?php echo $jobTemplateAssignment['employee']['employee_id']; ?>#job-template"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i>View Full Template
                        </a>
                        <a href="/employees/view-feedback.php?employee_id=<?php echo $jobTemplateAssignment['employee']['employee_id']; ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-comments me-1"></i>Feedback Hub
                        </a>
                        <a href="/self-assessment/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-pen me-1"></i>Self-Reflection
                        </a>
                        <a href="/achievements/journal.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-lightbulb me-1"></i>Achievement Journal
                        </a>
                        <a href="/idp/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-road me-1"></i>Development Plan
                        </a>
                        <?php endif; ?>
                        <?php if (isHRAdmin() && !empty($jobTemplateAssignment['template']['id'])): ?>
                        <a href="/admin/job_templates.php?edit=<?php echo $jobTemplateAssignment['template']['id']; ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sliders-h me-1"></i>Manage Template
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <h5 class="mb-1">Job template not available</h5>
                    <p class="text-muted mb-0">
                        We weren't able to find a job template linked to your profile yet. Once assigned, your KPIs,
                        responsibilities, skills, and values will appear here.
                    </p>
                    <div class="alert alert-<?php echo $jobTemplateAssignment['status'] === 'missing_profile' ? 'info' : 'warning'; ?> mt-3 mb-0">
                        <?php if ($jobTemplateAssignment['status'] === 'missing_profile'): ?>
                            <strong>Profile required:</strong> Link this user to an employee profile to unlock job template access.
                        <?php elseif ($jobTemplateAssignment['status'] === 'no_template'): ?>
                            <strong>No job template assigned.</strong> Contact HR to assign a template that matches your role.
                        <?php elseif ($jobTemplateAssignment['status'] === 'template_not_found'): ?>
                            <strong>Template not available.</strong> The assigned template could not be found or is inactive.
                        <?php elseif ($jobTemplateAssignment['status'] === 'error'): ?>
                            <strong>Unable to load template.</strong> Please refresh the page or try again later.
                        <?php else: ?>
                            <strong>Profile not found.</strong> We could not load your employee record.
                        <?php endif; ?>
                        
                        <?php if (isHRAdmin()): ?>
                            <div class="mt-2">
                                <?php if ($jobTemplateAssignment['employee']): ?>
                                <a href="/employees/edit.php?id=<?php echo $jobTemplateAssignment['employee']['employee_id']; ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-link me-1"></i>Assign Template
                                </a>
                                <?php else: ?>
                                <a href="/employees/create.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-user-plus me-1"></i>Create Employee Profile
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($jobTemplateAssignment['has_template'] && $jobTemplateAssignment['status'] === 'ready'): ?>
<?php
$kpiPreview = array_slice($jobTemplateAssignment['details']['kpis'], 0, 4);
$responsibilityPreview = array_slice($jobTemplateAssignment['details']['responsibilities'], 0, 4);
$technicalSkillPreview = array_slice($jobTemplateAssignment['details']['technical_skills'], 0, 4);
$softSkillPreview = array_slice($jobTemplateAssignment['details']['soft_skills'], 0, 4);
$valuePreview = array_slice($jobTemplateAssignment['details']['values'], 0, 4);
?>
<div class="row job-template-grid g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="template-section-card h-100" data-accent="primary">
            <div class="template-section-header">
                <div class="template-section-heading">
                    <span class="template-section-icon">
                        <i class="fas fa-bullseye"></i>
                    </span>
                    <div>
                        <span class="section-eyebrow">Key KPIs</span>
                        <p class="mb-0 small text-muted">What success looks like</p>
                    </div>
                </div>
                <span class="section-count-pill">
                    <i class="fas fa-chart-line me-1"></i><?php echo number_format($jobTemplateAssignment['counts']['kpis']); ?>
                </span>
            </div>
            <div class="template-section-body">
                <?php if (!empty($kpiPreview)): ?>
                    <?php foreach ($kpiPreview as $kpi): 
                        $targetValue = $kpi['target_value'] ?? null;
                        $unit = $kpi['measurement_unit'] ?? '';
                        $targetLabel = $targetValue !== null && $targetValue !== '' 
                            ? trim($targetValue . ' ' . $unit)
                            : 'No target set';
                    ?>
                    <div class="template-item">
                        <div class="item-title"><?php echo htmlspecialchars($kpi['kpi_name']); ?></div>
                        <div class="item-meta text-muted small">
                            Target: <?php echo htmlspecialchars($targetLabel); ?> • 
                            Weight: <?php echo number_format((float)($kpi['weight_percentage'] ?? 0), 1); ?>%
                        </div>
                        <?php if (!empty($kpi['kpi_description'])): ?>
                        <p class="item-description mb-0"><?php echo htmlspecialchars($kpi['kpi_description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php $remainingKpis = $jobTemplateAssignment['counts']['kpis'] - count($kpiPreview); ?>
                    <?php if ($remainingKpis > 0): ?>
                    <p class="text-muted small mb-0">+<?php echo $remainingKpis; ?> more KPIs defined in this template.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">KPIs have not been configured for this template.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="template-section-card h-100" data-accent="success">
            <div class="template-section-header">
                <div class="template-section-heading">
                    <span class="template-section-icon">
                        <i class="fas fa-list-check"></i>
                    </span>
                    <div>
                        <span class="section-eyebrow">Responsibilities</span>
                        <p class="mb-0 small text-muted">What you own</p>
                    </div>
                </div>
                <span class="section-count-pill">
                    <i class="fas fa-clipboard-check me-1"></i><?php echo number_format($jobTemplateAssignment['counts']['responsibilities']); ?>
                </span>
            </div>
            <div class="template-section-body">
                <?php if (!empty($responsibilityPreview)): ?>
                    <?php foreach ($responsibilityPreview as $responsibility): ?>
                    <div class="template-item">
                        <div class="item-title"><?php echo htmlspecialchars($responsibility['responsibility_text']); ?></div>
                        <div class="item-meta text-muted small">
                            Weight: <?php echo number_format((float)($responsibility['weight_percentage'] ?? 0), 1); ?>%
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php $remainingResponsibilities = $jobTemplateAssignment['counts']['responsibilities'] - count($responsibilityPreview); ?>
                    <?php if ($remainingResponsibilities > 0): ?>
                    <p class="text-muted small mb-0">+<?php echo $remainingResponsibilities; ?> more responsibilities inside the template.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Responsibilities are not yet defined for this template.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="template-section-card h-100" data-accent="info">
            <div class="template-section-header">
                <div class="template-section-heading">
                    <span class="template-section-icon">
                        <i class="fas fa-microchip"></i>
                    </span>
                    <div>
                        <span class="section-eyebrow">Technical Skills</span>
                        <p class="mb-0 small text-muted">Capabilities to master</p>
                    </div>
                </div>
                <span class="section-count-pill">
                    <i class="fas fa-code me-1"></i><?php echo number_format($jobTemplateAssignment['counts']['technical_skills']); ?>
                </span>
            </div>
            <div class="template-section-body">
                <?php if (!empty($technicalSkillPreview)): ?>
                    <?php foreach ($technicalSkillPreview as $skill): ?>
                    <div class="template-item">
                        <div class="item-title"><?php echo htmlspecialchars($skill['competency_name']); ?></div>
                        <div class="item-meta text-muted small">
                            Required: <?php echo htmlspecialchars($skill['required_level'] ?? 'Level TBD'); ?>
                        </div>
                        <?php if (!empty($skill['category_name'])): ?>
                        <p class="item-description mb-0"><?php echo htmlspecialchars($skill['category_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php $remainingTechnical = $jobTemplateAssignment['counts']['technical_skills'] - count($technicalSkillPreview); ?>
                    <?php if ($remainingTechnical > 0): ?>
                    <p class="text-muted small mb-0">+<?php echo $remainingTechnical; ?> more technical skills tracked.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Technical skill expectations will appear here once defined.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="template-section-card h-100" data-accent="warning">
            <div class="template-section-header">
                <div class="template-section-heading">
                    <span class="template-section-icon">
                        <i class="fas fa-people-group"></i>
                    </span>
                    <div>
                        <span class="section-eyebrow">Core Behaviors</span>
                        <p class="mb-0 small text-muted">Soft skills & cues</p>
                    </div>
                </div>
                <span class="section-count-pill">
                    <i class="fas fa-user-check me-1"></i><?php echo number_format($jobTemplateAssignment['counts']['soft_skills']); ?>
                </span>
            </div>
            <div class="template-section-body">
                <?php if (!empty($softSkillPreview)): ?>
                    <?php foreach ($softSkillPreview as $skill): ?>
                    <div class="template-item">
                        <div class="item-title"><?php echo htmlspecialchars($skill['competency_name']); ?></div>
                        <div class="item-meta text-muted small">
                            Expectation: <?php echo htmlspecialchars($skill['required_level'] ?? 'Level TBD'); ?>
                        </div>
                        <?php if (!empty($skill['soft_skill_definition'])): ?>
                        <p class="item-description mb-0"><?php echo htmlspecialchars($skill['soft_skill_definition']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php $remainingSoft = $jobTemplateAssignment['counts']['soft_skills'] - count($softSkillPreview); ?>
                    <?php if ($remainingSoft > 0): ?>
                    <p class="text-muted small mb-0">+<?php echo $remainingSoft; ?> more behaviors defined.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Soft skill guidance will appear once defined by HR.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-4">
        <div class="template-section-card h-100" data-accent="danger">
            <div class="template-section-header">
                <div class="template-section-heading">
                    <span class="template-section-icon">
                        <i class="fas fa-heart"></i>
                    </span>
                    <div>
                        <span class="section-eyebrow">Company Values</span>
                        <p class="mb-0 small text-muted">Cultural anchors</p>
                    </div>
                </div>
                <span class="section-count-pill">
                    <i class="fas fa-heartbeat me-1"></i><?php echo number_format($jobTemplateAssignment['counts']['values']); ?>
                </span>
            </div>
            <div class="template-section-body">
                <?php if (!empty($valuePreview)): ?>
                    <?php foreach ($valuePreview as $value): ?>
                    <div class="template-item">
                        <div class="item-title"><?php echo htmlspecialchars($value['value_name']); ?></div>
                        <?php if (!empty($value['description'])): ?>
                        <p class="item-description mb-0"><?php echo htmlspecialchars($value['description']); ?></p>
                        <?php endif; ?>
                        <div class="item-meta text-muted small">
                            Weight: <?php echo number_format((float)($value['weight_percentage'] ?? 0), 1); ?>%
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php $remainingValues = $jobTemplateAssignment['counts']['values'] - count($valuePreview); ?>
                    <?php if ($remainingValues > 0): ?>
                    <p class="text-muted small mb-0">+<?php echo $remainingValues; ?> additional values tracked.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Company values linked to this template will appear here.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php elseif ($jobTemplateAssignment['has_template'] && $jobTemplateAssignment['status'] === 'summary_only'): ?>
<div class="alert alert-warning mb-4">
    <strong>Heads up:</strong> We could load your template summary but not the detailed components. Refresh the page or contact HR if the issue persists.
</div>
<?php endif; ?>

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
            <div class="widget-title">Approved</div>
            <div class="widget-value"><?php echo number_format($dashboardData['approved_evaluations']); ?></div>
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
                    <a href="/employees/list.php" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>View Team
                    </a>
                    <a href="/evaluation/create.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Create Evaluation
                    </a>
                    <a href="/evaluation/my-evaluations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-clipboard-list me-2"></i>My Evaluations
                    </a>
                    <?php else: ?>
                    <?php if ($jobTemplateAssignment['has_template'] && !empty($jobTemplateAssignment['employee']['employee_id'])): ?>
                    <a href="/employees/view.php?id=<?php echo $jobTemplateAssignment['employee']['employee_id']; ?>#job-template" class="btn btn-outline-primary">
                        <i class="fas fa-briefcase me-2"></i>Review Job Template
                    </a>
                    <?php endif; ?>
                    <a href="/evaluation/my-evaluations.php" class="btn btn-primary">
                        <i class="fas fa-clipboard-list me-2"></i>My Evaluations
                    </a>
                    <a href="/self-assessment/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-pen me-2"></i>Give Self-Feedback
                    </a>
                    <a href="/achievements/journal.php" class="btn btn-outline-secondary">
                        <i class="fas fa-lightbulb me-2"></i>Log Achievement
                    </a>
                    <?php if (!empty($_SESSION['employee_id'])): ?>
                    <a href="/employees/view-feedback.php?employee_id=<?php echo $_SESSION['employee_id']; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-comments me-2"></i>Feedback Hub
                    </a>
                    <?php endif; ?>
                    <a href="/idp/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-road me-2"></i>Development Plan
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
