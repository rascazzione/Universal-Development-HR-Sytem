<?php
/**
 * Manager Dashboard - Team Evidence-Based Performance Insights
 * Phase 2: Dashboard & Analytics Implementation
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/DashboardAnalytics.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require manager or HR admin authentication
requireRole(['manager', 'hr_admin']);

$pageTitle = 'Manager Dashboard - Team Analytics';
$pageHeader = true;
$pageDescription = 'Evidence-based team performance insights and analytics';

// Get current user info
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'];
$managerId = $_SESSION['employee_id'];

// Initialize classes
$analytics = new DashboardAnalytics();
$employeeClass = new Employee();
$periodClass = new EvaluationPeriod();

// Get filters from request
$filters = [];
if (isset($_GET['period_id'])) $filters['period_id'] = (int)$_GET['period_id'];
if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];

// Get dashboard data
try {
    $dashboardData = $analytics->getManagerDashboardData($managerId, $filters);
    $periods = $periodClass->getPeriods(1, 10)['periods'];
} catch (Exception $e) {
    error_log("Manager dashboard error: " . $e->getMessage());
    $dashboardData = [];
    $periods = [];
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
                        <i class="fas fa-users me-2 text-primary"></i>
                        Team Performance Dashboard
                    </h1>
                    <p class="text-muted mb-0">Evidence-based insights for your team</p>
                </div>
                <div class="d-flex gap-2">
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
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                    
                    <!-- Refresh Button -->
                    <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Team Size</div>
                            <div class="metric-value"><?php echo $dashboardData['team_overview']['team_size'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-success">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Avg Team Rating</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['team_overview']['avg_team_rating'] ?? 0, 1); ?></div>
                            <div class="metric-subtitle">out of 5.0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-info">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Evidence Entries</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['team_overview']['evidence_entries_total'] ?? 0); ?></div>
                            <div class="metric-subtitle">this period</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-warning">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Active Evaluations</div>
                            <div class="metric-value"><?php echo $dashboardData['team_overview']['active_evaluations'] ?? 0; ?></div>
                            <div class="metric-subtitle">in progress</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Row -->
    <div class="row mb-4">
        <!-- Evidence Trends Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Team Evidence Trends
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="trendView" id="trendMonthly" checked>
                        <label class="btn btn-outline-primary" for="trendMonthly">Monthly</label>
                        
                        <input type="radio" class="btn-check" name="trendView" id="trendDimension">
                        <label class="btn btn-outline-primary" for="trendDimension">By Dimension</label>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="evidenceTrendsChart" height="300"></canvas>
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
                        <?php if (isset($dashboardData['performance_insights']['performance_distribution'])): ?>
                        <?php foreach ($dashboardData['performance_insights']['performance_distribution'] as $category => $count): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="performance-category-<?php echo strtolower(str_replace(' ', '_', $category)); ?>">
                                <?php echo ucfirst($category); ?>
                            </span>
                            <span class="badge bg-secondary"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Insights Row -->
    <div class="row mb-4">
        <!-- Team Comparison -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>
                        Team Comparison by Dimension
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="teamComparisonChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Feedback Analytics -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Feedback Frequency Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="metric-value text-primary">
                                <?php echo number_format($dashboardData['feedback_analytics']['avg_frequency'] ?? 0, 2); ?>
                            </div>
                            <div class="metric-label small">Avg Daily Frequency</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value text-success">
                                <?php echo $dashboardData['feedback_analytics']['total_active_employees'] ?? 0; ?>
                            </div>
                            <div class="metric-label small">Active Employees</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value text-info">
                                <?php echo number_format($dashboardData['quality_indicators']['avg_content_length'] ?? 0, 0); ?>
                            </div>
                            <div class="metric-label small">Avg Content Length</div>
                        </div>
                    </div>
                    <canvas id="feedbackFrequencyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Coaching Opportunities & Team Members -->
    <div class="row mb-4">
        <!-- Coaching Opportunities -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Coaching Opportunities
                    </h5>
                    <span class="badge bg-warning">
                        <?php echo count($dashboardData['coaching_opportunities']['opportunities'] ?? []); ?> identified
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['coaching_opportunities']['opportunities'])): ?>
                    <div class="coaching-opportunities-list">
                        <?php foreach ($dashboardData['coaching_opportunities']['opportunities'] as $opportunity): ?>
                        <div class="coaching-opportunity-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">
                                    Employee ID: <?php echo $opportunity['employee_id']; ?>
                                </h6>
                                <span class="badge bg-<?php echo $opportunity['opportunities'][0]['priority'] === 'high' ? 'danger' : 'warning'; ?>">
                                    <?php echo ucfirst($opportunity['opportunities'][0]['priority']); ?> Priority
                                </span>
                            </div>
                            <?php foreach ($opportunity['opportunities'] as $opp): ?>
                            <div class="opportunity-detail mb-1">
                                <strong><?php echo ucfirst($opp['dimension']); ?>:</strong>
                                <?php echo htmlspecialchars($opp['reason']); ?>
                                <small class="text-muted">(Rating: <?php echo $opp['avg_rating']; ?>, Entries: <?php echo $opp['entry_count']; ?>)</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">No immediate coaching opportunities identified. Great team performance!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Team Members Quick View -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Team Members
                    </h5>
                    <a href="/employees/list.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['team_members'])): ?>
                    <div class="team-members-list">
                        <?php foreach (array_slice($dashboardData['team_members'], 0, 8) as $member): ?>
                        <div class="team-member-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div>
                                <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($member['position'] ?? ''); ?></small>
                            </div>
                            <div class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/employees/view.php?id=<?php echo $member['employee_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/employees/give-feedback.php?id=<?php echo $member['employee_id']; ?>" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-comment"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No team members found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality Indicators -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-quality me-2"></i>
                        Evidence Quality Indicators
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="quality-metric">
                                <div class="metric-value text-primary">
                                    <?php echo $dashboardData['quality_indicators']['quality_metrics']['detailed_entries_pct'] ?? 0; ?>%
                                </div>
                                <div class="metric-label">Detailed Entries</div>
                                <small class="text-muted">Entries > 100 characters</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="quality-metric">
                                <div class="metric-value text-warning">
                                    <?php echo $dashboardData['quality_indicators']['quality_metrics']['brief_entries_pct'] ?? 0; ?>%
                                </div>
                                <div class="metric-label">Brief Entries</div>
                                <small class="text-muted">Entries < 50 characters</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="quality-metric">
                                <div class="metric-value text-success">
                                    <?php echo $dashboardData['quality_indicators']['quality_metrics']['employees_with_evidence'] ?? 0; ?>
                                </div>
                                <div class="metric-label">Active Employees</div>
                                <small class="text-muted">With evidence entries</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="quality-metric">
                                <div class="metric-value text-info">
                                    <?php echo $dashboardData['quality_indicators']['quality_metrics']['dimensions_covered'] ?? 0; ?>
                                </div>
                                <div class="metric-label">Dimensions Covered</div>
                                <small class="text-muted">Out of 4 total</small>
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
window.managerId = <?php echo $managerId; ?>;
</script>

<!-- Dashboard JavaScript -->
<script src="/assets/js/dashboard-manager.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>