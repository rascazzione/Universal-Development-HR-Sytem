<?php
/**
 * Quick Actions Component
 * Reusable quick actions rendering for all user roles
 */

/**
 * Render quick actions based on user role
 */
function renderQuickActions($userRole, $jobTemplateAssignment = null) {
    ob_start();
    ?>
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
                        
                    <?php else: // employee ?>
                        <?php if ($jobTemplateAssignment && $jobTemplateAssignment['has_template'] && !empty($jobTemplateAssignment['employee']['employee_id'])): ?>
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
                <?php 
                global $dashboardData;
                if (isset($dashboardData['current_period']) && $dashboardData['current_period']): 
                ?>
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
    <?php
    return ob_get_clean();
}
?>
