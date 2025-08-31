<?php
/**
 * HR Analytics Dashboard - Organizational Performance Insights
 * Phase 2: Dashboard & Analytics Implementation
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/DashboardAnalytics.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require HR admin authentication
requireRole(['hr_admin']);

$pageTitle = 'HR Analytics Dashboard';
$pageHeader = true;
$pageDescription = 'Organizational evidence-based performance insights and system analytics';

// Get current user info
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'];

// Initialize classes
$analytics = new DashboardAnalytics();
$employeeClass = new Employee();
$periodClass = new EvaluationPeriod();

// Get filters from request
$filters = [];
if (isset($_GET['period_id'])) $filters['period_id'] = (int)$_GET['period_id'];
if (isset($_GET['department'])) $filters['department'] = $_GET['department'];
if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];

// Get dashboard data
try {
    $dashboardData = $analytics->getHRAnalyticsDashboard($filters);
    $periods = $periodClass->getPeriods(1, 10)['periods'];
    $departments = $employeeClass->getDepartments();
} catch (Exception $e) {
    error_log("HR analytics dashboard error: " . $e->getMessage());
    $dashboardData = [];
    $periods = [];
    $departments = [];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        HR Analytics Dashboard
                    </h1>
                    <p class="text-muted mb-0">Organizational performance insights and system analytics</p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Department Filter -->
                    <select id="departmentFilter" class="form-select" style="width: auto;">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo (isset($_GET['department']) && $_GET['department'] == $dept) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Period Filter -->
                    <select id="periodFilter" class="form-select" style="width: auto;">
                        <option value="">Current Period</option>
                        <?php foreach ($periods as $period): ?>
                        <option value="<?php echo $period['period_id']; ?>" 
                                <?php echo (isset($_GET['period_id']) && $_GET['period_id'] == $period['period_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($period['period_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Export Button -->
                    <button class="btn btn-outline-primary" onclick="exportDashboard()">
                        <i class="fas fa-download me-1"></i>Export Report
                    </button>
                    
                    <!-- Refresh Button -->
                    <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Organizational Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-2-4 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Total Employees</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['organizational_overview']['total_employees'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2-4 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-success">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Evidence Entries</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['organizational_overview']['total_evidence_entries'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2-4 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-info">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Avg Rating</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['organizational_overview']['avg_organizational_rating'] ?? 0, 1); ?></div>
                            <div class="metric-subtitle">out of 5.0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2-4 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-warning">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Active Evaluations</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['organizational_overview']['active_evaluations'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2-4 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-danger">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">System Adoption</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['organizational_overview']['system_adoption_rate'] ?? 0, 1); ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Row -->
    <div class="row mb-4">
        <!-- Department Comparison Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Department Performance Comparison
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="deptView" id="deptRating" checked>
                        <label class="btn btn-outline-primary" for="deptRating">Avg Rating</label>
                        
                        <input type="radio" class="btn-check" name="deptView" id="deptEntries">
                        <label class="btn btn-outline-primary" for="deptEntries">Evidence Volume</label>
                        
                        <input type="radio" class="btn-check" name="deptView" id="deptEmployees">
                        <label class="btn btn-outline-primary" for="deptEmployees">Active Users</label>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="departmentComparisonChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Performance Distribution -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Performance Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceDistributionChart" height="250"></canvas>
                    <div class="mt-3">
                        <?php if (isset($dashboardData['performance_distribution']['distribution'])): ?>
                        <?php foreach ($dashboardData['performance_distribution']['distribution'] as $category): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="performance-category-<?php echo strtolower(str_replace(' ', '_', $category['performance_category'])); ?>">
                                <?php echo $category['performance_category']; ?>
                            </span>
                            <span class="badge bg-secondary"><?php echo $category['entry_count']; ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Usage & Adoption Row -->
    <div class="row mb-4">
        <!-- Usage Analytics -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        System Usage Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="metric-value text-primary">
                                <?php echo $dashboardData['usage_analytics']['usage_trends']['trend'] ?? 'stable'; ?>
                            </div>
                            <div class="metric-label small">Usage Trend</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value text-success">
                                <?php echo number_format($dashboardData['usage_analytics']['usage_trends']['change_percentage'] ?? 0, 1); ?>%
                            </div>
                            <div class="metric-label small">Change Rate</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value text-info">
                                <?php echo $dashboardData['usage_analytics']['manager_activity']['entries_created'] ?? 0; ?>
                            </div>
                            <div class="metric-label small">Manager Entries</div>
                        </div>
                    </div>
                    <canvas id="usageAnalyticsChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Adoption Metrics -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i>
                        System Adoption Metrics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="adoption-metrics">
                        <div class="metric-item mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Overall Adoption Rate</span>
                                <span class="fw-bold"><?php echo number_format($dashboardData['adoption_metrics']['adoption_rate'] ?? 0, 1); ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: <?php echo $dashboardData['adoption_metrics']['adoption_rate'] ?? 0; ?>%"
                                     aria-valuenow="<?php echo $dashboardData['adoption_metrics']['adoption_rate'] ?? 0; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">
                                <?php echo $dashboardData['adoption_metrics']['active_users'] ?? 0; ?> of 
                                <?php echo $dashboardData['adoption_metrics']['total_employees'] ?? 0; ?> employees active
                            </small>
                        </div>
                        
                        <div class="adoption-status">
                            <h6 class="mb-3">Adoption Status</h6>
                            <?php 
                            $adoptionStatus = $dashboardData['adoption_metrics']['adoption_status'] ?? 'low';
                            $statusColor = $adoptionStatus === 'excellent' ? 'success' : 
                                         ($adoptionStatus === 'good' ? 'info' : 
                                         ($adoptionStatus === 'moderate' ? 'warning' : 'danger'));
                            ?>
                            <div class="alert alert-<?php echo $statusColor; ?> mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong><?php echo ucfirst($adoptionStatus); ?> Adoption</strong>
                                <br>
                                <small>
                                    <?php
                                    switch ($adoptionStatus) {
                                        case 'excellent':
                                            echo 'System is widely adopted across the organization.';
                                            break;
                                        case 'good':
                                            echo 'Good adoption rate with room for improvement.';
                                            break;
                                        case 'moderate':
                                            echo 'Moderate adoption - consider training initiatives.';
                                            break;
                                        default:
                                            echo 'Low adoption - immediate action required.';
                                    }
                                    ?>
                                </small>
                            </div>
                            
                            <div class="adoption-actions">
                                <h6 class="mb-2">Recommended Actions</h6>
                                <ul class="list-unstyled">
                                    <?php if ($adoptionStatus === 'low' || $adoptionStatus === 'moderate'): ?>
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Conduct system training sessions</li>
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Increase manager engagement</li>
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Implement adoption incentives</li>
                                    <?php else: ?>
                                    <li><i class="fas fa-check text-success me-2"></i>Maintain current engagement levels</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Share success stories</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Organizational Patterns & Reporting Insights -->
    <div class="row mb-4">
        <!-- Organizational Evidence Patterns -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-network-wired me-2"></i>
                        Organizational Evidence Patterns
                    </h5>
                </div>
                <div class="card-body">
                    <div class="patterns-grid">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="pattern-metric">
                                    <div class="metric-value text-primary">
                                        <?php echo number_format($dashboardData['organizational_patterns']['total_entries'] ?? 0); ?>
                                    </div>
                                    <div class="metric-label">Total Entries</div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="pattern-metric">
                                    <div class="metric-value text-success">
                                        <?php echo number_format($dashboardData['organizational_patterns']['avg_rating'] ?? 0, 1); ?>
                                    </div>
                                    <div class="metric-label">Avg Rating</div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="pattern-metric">
                                    <div class="metric-value text-info">
                                        <?php echo $dashboardData['organizational_patterns']['active_employees'] ?? 0; ?>
                                    </div>
                                    <div class="metric-label">Active Employees</div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="pattern-metric">
                                    <div class="metric-value text-warning">
                                        <?php echo $dashboardData['organizational_patterns']['active_days'] ?? 0; ?>
                                    </div>
                                    <div class="metric-label">Active Days</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Evidence Quality Insights</h6>
                        <?php if (isset($dashboardData['reporting_insights']['quality_insights'])): ?>
                        <div class="quality-insights">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Avg Content Length</span>
                                <span class="fw-bold"><?php echo number_format($dashboardData['reporting_insights']['quality_insights']['avg_content_length'] ?? 0, 0); ?> chars</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Detailed Entries</span>
                                <span class="fw-bold"><?php echo $dashboardData['reporting_insights']['quality_insights']['detailed_count'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Brief Entries</span>
                                <span class="fw-bold"><?php echo $dashboardData['reporting_insights']['quality_insights']['brief_count'] ?? 0; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reporting Insights & Recommendations -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Evidence-Based Reporting Insights
                    </h5>
                </div>
                <div class="card-body">
                    <div class="insights-section">
                        <h6 class="mb-3">Dimension Performance</h6>
                        <?php if (!empty($dashboardData['reporting_insights']['dimension_insights'])): ?>
                        <div class="dimension-insights mb-4">
                            <?php foreach ($dashboardData['reporting_insights']['dimension_insights'] as $dimension): ?>
                            <div class="dimension-item mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?php echo ucfirst($dimension['dimension']); ?></span>
                                    <span class="badge bg-primary"><?php echo number_format($dimension['avg_rating'], 1); ?>/5.0</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo ($dimension['avg_rating'] / 5) * 100; ?>%"
                                         aria-valuenow="<?php echo $dimension['avg_rating']; ?>" 
                                         aria-valuemin="0" aria-valuemax="5">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $dimension['entry_count']; ?> entries, 
                                    <?php echo $dimension['employees_assessed']; ?> employees
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <h6 class="mb-3">System Recommendations</h6>
                        <?php if (!empty($dashboardData['reporting_insights']['reporting_recommendations'])): ?>
                        <div class="recommendations-list">
                            <?php foreach ($dashboardData['reporting_insights']['reporting_recommendations'] as $recommendation): ?>
                            <div class="recommendation-item mb-2 p-2 border-start border-3 border-warning">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <small><?php echo htmlspecialchars($recommendation); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <small>System is performing well. No immediate recommendations.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 360-Degree Features System Administration -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sync-alt me-2"></i>
                        360° System Administration
                    </h5>
                    <a href="/360-features/index.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-th me-1"></i>View All Features
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="admin-quick-action">
                                <div class="text-center mb-3">
                                    <div class="action-icon-admin mb-2">
                                        <i class="fas fa-gift fa-3x text-warning"></i>
                                    </div>
                                    <h6>KUDOS Categories</h6>
                                    <small class="text-muted">Manage recognition types</small>
                                </div>
                                <div class="stats-bar mb-2">
                                    <div class="d-flex justify-content-between">
                                        <small>Active Categories</small>
                                        <span class="badge bg-warning">8</span>
                                    </div>
                                </div>
                                <a href="/admin/kudos-categories.php" class="btn btn-sm btn-outline-warning w-100">Configure</a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="admin-quick-action">
                                <div class="text-center mb-3">
                                    <div class="action-icon-admin mb-2">
                                        <i class="fas fa-arrow-up fa-3x text-danger"></i>
                                    </div>
                                    <h6>Upward Feedback</h6>
                                    <small class="text-muted">Manage feedback processes</small>
                                </div>
                                <div class="stats-bar mb-2">
                                    <div class="d-flex justify-content-between">
                                        <small>Active Programs</small>
                                        <span class="badge bg-danger">3</span>
                                    </div>
                                </div>
                                <a href="/admin/upward-feedback.php" class="btn btn-sm btn-outline-danger w-100">Manage</a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="admin-quick-action">
                                <div class="text-center mb-3">
                                    <div class="action-icon-admin mb-2">
                                        <i class="fas fa-chart-line fa-3x text-info"></i>
                                    </div>
                                    <h6>360° Analytics</h6>
                                    <small class="text-muted">Comprehensive insights</small>
                                </div>
                                <div class="stats-bar mb-2">
                                    <div class="d-flex justify-content-between">
                                        <small>Reports Available</small>
                                        <span class="badge bg-info">12</span>
                                    </div>
                                </div>
                                <a href="/admin/360-analytics.php" class="btn btn-sm btn-outline-info w-100">Analytics</a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="admin-quick-action">
                                <div class="text-center mb-3">
                                    <div class="action-icon-admin mb-2">
                                        <i class="fas fa-cogs fa-3x text-primary"></i>
                                    </div>
                                    <h6>Feature Settings</h6>
                                    <small class="text-muted">System configuration</small>
                                </div>
                                <div class="stats-bar mb-2">
                                    <div class="d-flex justify-content-between">
                                        <small>Active Features</small>
                                        <span class="badge bg-primary">5/5</span>
                                    </div>
                                </div>
                                <a href="/admin/360-settings.php" class="btn btn-sm btn-outline-primary w-100">Settings</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 360-Degree System Health & Adoption -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        360° System Health
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="health-metric">
                                <div class="metric-value text-success">
                                    <i class="fas fa-check-circle"></i> 100%
                                </div>
                                <div class="metric-label">System Uptime</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="health-metric">
                                <div class="metric-value text-primary">
                                    <i class="fas fa-users"></i> 92%
                                </div>
                                <div class="metric-label">User Adoption</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="health-metric">
                                <div class="metric-value text-info">
                                    <i class="fas fa-chart-line"></i> Good
                                </div>
                                <div class="metric-label">Performance</div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Feature Status</h6>
                    <div class="feature-status-list">
                        <div class="feature-status-item mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-alt text-primary me-2"></i>Self-Assessment</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <div class="feature-status-item mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-trophy text-success me-2"></i>Achievements</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <div class="feature-status-item mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-gift text-warning me-2"></i>KUDOS</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <div class="feature-status-item mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-bullseye text-info me-2"></i>OKR</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <div class="feature-status-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-arrow-up text-danger me-2"></i>Upward Feedback</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        360° Usage Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Feature Usage This Month</h6>
                    <canvas id="featureUsageChart" height="200"></canvas>
                    
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="usage-stat">
                                    <div class="stat-number">1,247</div>
                                    <div class="stat-label small">Self-Assessments</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="usage-stat">
                                    <div class="stat-number">856</div>
                                    <div class="stat-label small">Achievements</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="usage-stat">
                                    <div class="stat-number">423</div>
                                    <div class="stat-label small">KUDOS Given</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="usage-stat">
                                    <div class="stat-number">189</div>
                                    <div class="stat-label small">OKR Updates</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & System Health -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        System Management & Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h6>Manage Employees</h6>
                                <p class="text-muted small">Add, edit, or manage employee records</p>
                                <a href="/employees/list.php" class="btn btn-outline-primary btn-sm">
                                    Manage
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-calendar-alt fa-2x text-success mb-2"></i>
                                <h6>Evaluation Periods</h6>
                                <p class="text-muted small">Configure evaluation periods and cycles</p>
                                <a href="/admin/periods.php" class="btn btn-outline-success btn-sm">
                                    Configure
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-file-export fa-2x text-info mb-2"></i>
                                <h6>360° Reports</h6>
                                <p class="text-muted small">Export comprehensive 360° reports</p>
                                <a href="/admin/360-analytics.php" class="btn btn-outline-info btn-sm">
                                    Export
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-cog fa-2x text-warning mb-2"></i>
                                <h6>System Settings</h6>
                                <p class="text-muted small">Configure system parameters and settings</p>
                                <a href="/admin/settings.php" class="btn btn-outline-warning btn-sm">
                                    Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Data for JavaScript -->
<script>
window.dashboardData = <?php echo json_encode($dashboardData); ?>;
window.userRole = '<?php echo $userRole; ?>';
</script>

<!-- Dashboard JavaScript -->
<script src="/assets/js/dashboard-hr.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>