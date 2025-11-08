<?php
/**
 * Dashboard Widgets Component
 * Reusable widget rendering for all user roles
 */

/**
 * Render a single dashboard widget
 */
function renderDashboardWidget($title, $value, $icon, $link = null, $changeText = null, $changeLink = null, $color = 'primary') {
    $colorClasses = [
        'primary' => 'widget-icon primary',
        'success' => 'widget-icon success', 
        'warning' => 'widget-icon warning',
        'info' => 'widget-icon info',
        'danger' => 'widget-icon danger'
    ];
    
    $iconClass = $colorClasses[$color] ?? $colorClasses['primary'];
    
    ob_start();
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-widget">
            <div class="<?php echo $iconClass; ?>">
                <i class="<?php echo $icon; ?>"></i>
            </div>
            <div class="widget-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="widget-value"><?php echo htmlspecialchars($value); ?></div>
            <?php if ($changeText): ?>
            <div class="widget-change">
                <?php if ($changeLink): ?>
                    <a href="<?php echo htmlspecialchars($changeLink); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($changeText); ?>
                    </a>
                <?php else: ?>
                    <span class="text-decoration-none"><?php echo htmlspecialchars($changeText); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render HR Admin widgets
 */
function renderHRAdminWidgets($dashboardData) {
    ob_start();
    ?>
    <div class="row">
        <?php
        echo renderDashboardWidget(
            'Total Employees',
            number_format($dashboardData['total_employees']),
            'fas fa-users',
            '/employees/list.php',
            'View all employees'
        );
        
        echo renderDashboardWidget(
            'Total Evaluations', 
            number_format($dashboardData['total_evaluations']),
            'fas fa-clipboard-check',
            '/evaluation/list.php',
            'View all evaluations'
        );
        
        echo renderDashboardWidget(
            'Pending Evaluations',
            number_format($dashboardData['pending_evaluations']),
            'fas fa-clock',
            '/evaluation/list.php?status=draft',
            'View pending'
        );
        
        echo renderDashboardWidget(
            'Active Periods',
            number_format($dashboardData['active_periods']),
            'fas fa-calendar-alt',
            '/admin/periods.php',
            'Manage periods'
        );
        ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Manager widgets
 */
function renderManagerWidgets($dashboardData) {
    ob_start();
    ?>
    <div class="row">
        <?php
        echo renderDashboardWidget(
            'Team Members',
            number_format($dashboardData['team_size']),
            'fas fa-users',
            '/employees/team.php',
            'View team'
        );
        
        echo renderDashboardWidget(
            'Team Evaluations',
            number_format($dashboardData['team_evaluations']),
            'fas fa-clipboard-list',
            '/evaluation/my-evaluations.php',
            'View evaluations'
        );
        
        echo renderDashboardWidget(
            'Pending',
            number_format($dashboardData['pending_evaluations']),
            'fas fa-clock',
            '/evaluation/create.php',
            'Create evaluation'
        );
        
        echo renderDashboardWidget(
            'Approved',
            number_format($dashboardData['approved_evaluations']),
            'fas fa-check-circle',
            null,
            'This period',
            null,
            'success'
        );
        ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Employee widgets
 */
function renderEmployeeWidgets($dashboardData) {
    ob_start();
    ?>
    <div class="row">
        <?php
        // Latest Rating Widget
        $latestRating = 'N/A';
        $ratingDate = 'No evaluations yet';
        
        if ($dashboardData['latest_evaluation']) {
            $rating = $dashboardData['latest_evaluation']['overall_rating'] ?? null;
            if ($rating !== null && is_numeric($rating)) {
                $latestRating = number_format($rating, 1) . '/5.0';
            } else {
                $latestRating = 'Pending';
            }
            $ratingDate = formatDate($dashboardData['latest_evaluation']['created_at'], 'M Y');
        }
        
        echo renderDashboardWidget(
            'Latest Rating',
            $latestRating,
            'fas fa-star',
            null,
            $ratingDate
        );
        
        echo renderDashboardWidget(
            'Total Evaluations',
            number_format($dashboardData['total_evaluations']),
            'fas fa-clipboard-check',
            '/evaluation/my-evaluations.php',
            'View history'
        );
        
        // Current Period Widget
        $periodStatus = $dashboardData['current_period'] ? 'Active' : 'None';
        $periodText = $dashboardData['current_period'] 
            ? htmlspecialchars($dashboardData['current_period']['period_name'])
            : 'No active period';
            
        echo renderDashboardWidget(
            'Current Period',
            $periodStatus,
            'fas fa-calendar-alt',
            null,
            $periodText
        );
        ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Main function to render widgets based on user role
 */
function renderDashboardWidgets($userRole, $dashboardData) {
    switch ($userRole) {
        case 'hr_admin':
            return renderHRAdminWidgets($dashboardData);
        case 'manager':
            return renderManagerWidgets($dashboardData);
        case 'employee':
        default:
            return renderEmployeeWidgets($dashboardData);
    }
}
?>