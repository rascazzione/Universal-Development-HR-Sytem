<?php
/**
 * Employee Dashboard - Personal Performance Analytics
 * Phase 2: Dashboard & Analytics Implementation
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/DashboardAnalytics.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require authentication
requireAuth();

$pageTitle = 'My Performance Dashboard';
$pageHeader = true;
$pageDescription = 'Your personal evidence-based performance insights and development analytics';

// Get current user info
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'];
$employeeId = $_SESSION['employee_id'];

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
    $dashboardData = $analytics->getEmployeeDashboardData($employeeId, $filters);
    $periods = $periodClass->getPeriods(1, 10)['periods'];
    $employeeInfo = $employeeClass->getEmployeeById($employeeId);
} catch (Exception $e) {
    error_log("Employee dashboard error: " . $e->getMessage());
    $dashboardData = [];
    $periods = [];
    $employeeInfo = null;
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
                        <i class="fas fa-user-chart me-2 text-primary"></i>
                        My Performance Dashboard
                    </h1>
                    <p class="text-muted mb-0">Track your growth and development journey</p>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-primary">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Current Rating</div>
                            <div class="metric-value"><?php echo number_format($dashboardData['personal_overview']['current_rating'] ?? 0, 1); ?></div>
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
                        <div class="metric-icon bg-success">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Evidence Entries</div>
                            <div class="metric-value"><?php echo $dashboardData['personal_overview']['total_evidence_entries'] ?? 0; ?></div>
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
                        <div class="metric-icon bg-info">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Performance Trend</div>
                            <div class="metric-value">
                                <?php 
                                $trend = $dashboardData['personal_overview']['rating_trend'] ?? 'stable';
                                $trendIcon = $trend === 'improving' ? 'fa-arrow-up text-success' : 
                                           ($trend === 'declining' ? 'fa-arrow-down text-danger' : 'fa-minus text-muted');
                                ?>
                                <i class="fas <?php echo $trendIcon; ?>"></i>
                            </div>
                            <div class="metric-subtitle"><?php echo ucfirst($trend); ?></div>
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
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Dimensions Covered</div>
                            <div class="metric-value"><?php echo $dashboardData['personal_overview']['dimensions_covered'] ?? 0; ?></div>
                            <div class="metric-subtitle">out of 4</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends & Feedback Summary -->
    <div class="row mb-4">
        <!-- Performance Trends Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Performance Trend Analysis
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="trendView" id="trendOverall" checked>
                        <label class="btn btn-outline-primary" for="trendOverall">Overall</label>
                        
                        <input type="radio" class="btn-check" name="trendView" id="trendByDimension">
                        <label class="btn btn-outline-primary" for="trendByDimension">By Dimension</label>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Feedback Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Feedback Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="feedback-summary">
                        <div class="summary-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Entries</span>
                                <span class="badge bg-primary"><?php echo $dashboardData['feedback_summary']['total_entries'] ?? 0; ?></span>
                            </div>
                        </div>
                        
                        <div class="summary-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Positive Feedback</span>
                                <span class="badge bg-success"><?php echo $dashboardData['feedback_summary']['positive_entries'] ?? 0; ?></span>
                            </div>
                        </div>
                        
                        <div class="summary-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Development Areas</span>
                                <span class="badge bg-warning"><?php echo $dashboardData['feedback_summary']['negative_entries'] ?? 0; ?></span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Performance by Dimension</h6>
                        <?php if (!empty($dashboardData['feedback_summary']['dimension_breakdown'])): ?>
                        <?php foreach ($dashboardData['feedback_summary']['dimension_breakdown'] as $dimension): ?>
                        <div class="dimension-item mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted"><?php echo ucfirst($dimension['dimension']); ?></small>
                                <small class="fw-bold"><?php echo number_format($dimension['avg_rating'], 1); ?>/5.0</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo ($dimension['avg_rating'] / 5) * 100; ?>%"
                                     aria-valuenow="<?php echo $dimension['avg_rating']; ?>" 
                                     aria-valuemin="0" aria-valuemax="5">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 360-Degree Features Navigation Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sync-alt me-2"></i>
                        360° Quick Actions
                    </h5>
                    <a href="/360-features/index.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-th me-1"></i>View All Features
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-widget text-center" id="widget-self-assessment">
                                <div class="action-icon mb-2">
                                    <i class="fas fa-file-alt fa-2x text-primary"></i>
                                </div>
                                <h6 class="mb-1">Self-Assessment</h6>
                                <div id="saStatusWidget" class="mb-2">
                                    <div class="text-muted small">Loading...</div>
                                </div>
                                <a href="/self-assessment/dashboard.php" class="btn btn-sm btn-primary">Start</a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-widget text-center" id="widget-achievements">
                                <div class="action-icon mb-2">
                                    <i class="fas fa-trophy fa-2x text-success"></i>
                                </div>
                                <h6 class="mb-1">Achievements</h6>
                                <div id="achievementsWidget" class="mb-2">
                                    <div class="text-muted small">Loading...</div>
                                </div>
                                <a href="/achievements/journal.php" class="btn btn-sm btn-success">Journal</a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-widget text-center" id="widget-kudos">
                                <div class="action-icon mb-2">
                                    <i class="fas fa-gift fa-2x text-warning"></i>
                                </div>
                                <h6 class="mb-1">KUDOS</h6>
                                <div id="kudosWidget" class="mb-2">
                                    <div class="text-muted small">Loading...</div>
                                </div>
                                <a href="/kudos/feed.php" class="btn btn-sm btn-warning">Feed</a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                            <div class="quick-action-widget" id="widget-okr">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="action-icon me-3">
                                        <i class="fas fa-bullseye fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">OKR Progress</h6>
                                        <small class="text-muted">Active objectives summary</small>
                                    </div>
                                </div>
                                <div id="okrWidget" class="mb-2">
                                    <div class="text-muted small">Loading...</div>
                                </div>
                                <a href="/okr/dashboard.php" class="btn btn-sm btn-info">Manage OKRs</a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <div class="quick-action-widget" id="widget-idp">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="action-icon me-3">
                                        <i class="fas fa-graduation-cap fa-2x text-secondary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Development</h6>
                                        <small class="text-muted">IDP and learning progress</small>
                                    </div>
                                </div>
                                <div id="idpWidget" class="mb-2">
                                    <div class="text-muted small">Loading...</div>
                                </div>
                                <a href="/idp/dashboard.php" class="btn btn-sm btn-secondary">View IDP</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Notifications and Recommendations -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card" id="quick-actions-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Recommended Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="action-recommendation mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file-alt text-primary me-3"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Complete Self-Assessment</h6>
                                <small class="text-muted">Reflect on your performance and evidence</small>
                            </div>
                            <a href="/self-assessment/dashboard.php" class="btn btn-sm btn-primary">Start</a>
                        </div>
                    </div>
                    <div class="action-recommendation mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-trophy text-success me-3"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Document Recent Achievements</h6>
                                <small class="text-muted">Add your latest successes to your journal</small>
                            </div>
                            <a href="/achievements/journal.php" class="btn btn-sm btn-success">Add</a>
                        </div>
                    </div>
                    <div class="action-recommendation">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bullseye text-info me-3"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Update OKR Progress</h6>
                                <small class="text-muted">Track progress on your key objectives</small>
                            </div>
                            <a href="/okr/dashboard.php" class="btn btn-sm btn-info">Update</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" id="status-alerts-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>
                        Status & Notifications
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Getting Started:</strong> New to 360° features?
                        <a href="/360-features/index.php" class="alert-link">Take the tour</a>
                    </div>
                    <div class="status-item mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Self-Assessment Period</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                    <div class="status-item mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>KUDOS Points Available</span>
                            <span class="badge bg-warning">5</span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Pending Reviews</span>
                            <span class="badge bg-secondary">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Development & Goals Row (unchanged content preserved below) -->
    <div class="row mb-4">
        <!-- Development Recommendations -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Development Recommendations
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['development_recommendations'])): ?>
                    <div class="recommendations-list">
                        <?php foreach ($dashboardData['development_recommendations'] as $recommendation): ?>
                        <div class="recommendation-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0"><?php echo ucfirst($recommendation['dimension']); ?></h6>
                                <span class="badge bg-<?php echo $recommendation['priority'] === 'high' ? 'danger' : ($recommendation['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                    <?php echo ucfirst($recommendation['priority']); ?>
                                </span>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($recommendation['recommendation']); ?></p>
                            <?php if (isset($recommendation['current_rating'])): ?>
                            <small class="text-muted">
                                Current: <?php echo $recommendation['current_rating']; ?>/5.0
                                <?php if (isset($recommendation['target_rating'])): ?>
                                | Target: <?php echo $recommendation['target_rating']; ?>/5.0
                                <?php endif; ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-trophy fa-3x text-success mb-3"></i>
                        <p class="text-muted">Excellent performance! Keep up the great work.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Goal Progress -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-target me-2"></i>
                        Goal Progress Tracking
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['goal_progress'])): ?>
                    <div class="goals-list">
                        <?php foreach ($dashboardData['goal_progress'] as $goal): ?>
                        <div class="goal-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?php echo ucfirst($goal['dimension']); ?> Excellence</h6>
                                <span class="text-muted"><?php echo $goal['progress_percentage']; ?>%</span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: <?php echo $goal['progress_percentage']; ?>%"
                                     aria-valuenow="<?php echo $goal['progress_percentage']; ?>"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    Current: <?php echo number_format($goal['current_rating'], 1); ?>/5.0
                                </small>
                                <small class="text-muted">
                                    Target: <?php echo number_format($goal['target_rating'], 1); ?>/5.0
                                </small>
                            </div>
                            <small class="text-muted">
                                Evidence entries: <?php echo $goal['evidence_count']; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No goals set for this period.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Peer Comparison & Evidence History -->
    <div class="row mb-4">
        <!-- Peer Comparison -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Peer Comparison (Anonymized)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($dashboardData['peer_comparison']['comparison_available']): ?>
                    <div class="peer-comparison">
                        <p class="text-muted mb-3">
                            Compared to peers in <?php echo htmlspecialchars($dashboardData['peer_comparison']['department']); ?> department
                        </p>
                        
                        <?php foreach ($dashboardData['peer_comparison']['dimension_comparisons'] as $comparison): ?>
                        <div class="comparison-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo ucfirst($comparison['dimension']); ?></span>
                                <span class="badge bg-<?php echo $comparison['percentile'] >= 70 ? 'success' : ($comparison['percentile'] >= 50 ? 'warning' : 'danger'); ?>">
                                    <?php echo $comparison['percentile']; ?>th percentile
                                </span>
                            </div>
                            <div class="d-flex justify-content-between text-sm">
                                <span>Your rating: <?php echo number_format($comparison['employee_rating'], 1); ?></span>
                                <span>Dept avg: <?php echo number_format($comparison['department_avg'], 1); ?></span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $comparison['percentile']; ?>%"
                                     aria-valuenow="<?php echo $comparison['percentile']; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Peer comparison not available. Need more department data.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Evidence History Timeline -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Evidence History
                    </h5>
                    <span class="badge bg-info">
                        <?php echo $dashboardData['evidence_history']['total_entries'] ?? 0; ?> entries
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['evidence_history']['timeline'])): ?>
                    <div class="evidence-timeline" style="max-height: 300px; overflow-y: auto;">
                        <?php 
                        $timelineData = array_slice($dashboardData['evidence_history']['timeline'], 0, 10, true);
                        foreach ($timelineData as $date => $entries): 
                        ?>
                        <div class="timeline-item mb-3">
                            <div class="timeline-date text-muted small mb-1">
                                <?php echo date('M j, Y', strtotime($date)); ?>
                            </div>
                            <?php foreach ($entries as $entry): ?>
                            <div class="timeline-entry p-2 border-start border-3 border-primary ms-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="badge bg-secondary"><?php echo ucfirst($entry['dimension']); ?></span>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $entry['star_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-0 mt-1 small">
                                    <?php echo htmlspecialchars(substr($entry['content'], 0, 100)); ?>
                                    <?php if (strlen($entry['content']) > 100): ?>...<?php endif; ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No evidence entries found for this period.</p>
                        <a href="/employees/give-feedback.php?id=<?php echo $employeeId; ?>" class="btn btn-primary btn-sm">
                            Request Feedback
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Recommended Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-comment-dots fa-2x text-primary mb-2"></i>
                                <h6>Request Feedback</h6>
                                <p class="text-muted small">Get more evidence entries to improve assessment accuracy</p>
                                <a href="/employees/give-feedback.php?id=<?php echo $employeeId; ?>" class="btn btn-outline-primary btn-sm">
                                    Request Now
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                                <h6>View Evaluations</h6>
                                <p class="text-muted small">Review your formal evaluation history and progress</p>
                                <a href="/evaluation/my-evaluations.php" class="btn btn-outline-success btn-sm">
                                    View History
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="action-item p-3">
                                <i class="fas fa-user-edit fa-2x text-info mb-2"></i>
                                <h6>Update Profile</h6>
                                <p class="text-muted small">Keep your profile information current and complete</p>
                                <a href="/employees/edit.php?id=<?php echo $employeeId; ?>" class="btn btn-outline-info btn-sm">
                                    Edit Profile
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
window.employeeId = <?php echo $employeeId; ?>;
</script>

<!-- Dashboard JavaScript -->
<script src="/assets/js/dashboard-employee.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>