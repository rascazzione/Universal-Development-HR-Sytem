<?php
/**
 * Upward Feedback Dashboard for Managers
 * Provides managers with anonymous feedback from their team members
 */

require_once __DIR__ . '/../../includes/auth.php';

// Require manager or HR admin authentication
requireRole(['manager', 'hr_admin']);

$pageTitle = 'Upward Feedback Dashboard';
$pageHeader = true;
$pageDescription = 'Anonymous feedback and insights from your team members';

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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-arrow-up me-2 text-danger"></i>
                        Upward Feedback Dashboard
                    </h1>
                    <p class="text-muted mb-0">Anonymous feedback and insights from your team members</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportReport()">
                        <i class="fas fa-download me-1"></i>Export Report
                    </button>
                    <button class="btn btn-outline-secondary" onclick="initiateFeedback()">
                        <i class="fas fa-plus me-1"></i>Request Feedback
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-danger">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Total Feedback</div>
                            <div class="metric-value">24</div>
                            <div class="metric-subtitle">responses received</div>
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
                            <div class="metric-label">Average Rating</div>
                            <div class="metric-value">4.2</div>
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
                        <div class="metric-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Team Participation</div>
                            <div class="metric-value">85%</div>
                            <div class="metric-subtitle">response rate</div>
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
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="ms-3">
                            <div class="metric-label">Last Updated</div>
                            <div class="metric-value">2</div>
                            <div class="metric-subtitle">days ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Feedback Content -->
    <div class="row mb-4">
        <!-- Recent Feedback Summary -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Recent Feedback Summary
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="feedbackView" id="viewAll" checked>
                        <label class="btn btn-outline-primary" for="viewAll">All</label>
                        
                        <input type="radio" class="btn-check" name="feedbackView" id="viewPositive">
                        <label class="btn btn-outline-success" for="viewPositive">Positive</label>
                        
                        <input type="radio" class="btn-check" name="feedbackView" id="viewConstructive">
                        <label class="btn btn-outline-warning" for="viewConstructive">Constructive</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="feedback-list">
                        <div class="feedback-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success">Leadership</span>
                                <small class="text-muted">2 days ago</small>
                            </div>
                            <p class="mb-2">"Great job at leading the team through the recent project challenges. Your communication was clear and supportive throughout the process."</p>
                            <div class="feedback-rating">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <span class="ms-2 text-muted">5.0</span>
                            </div>
                        </div>
                        
                        <div class="feedback-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-warning">Communication</span>
                                <small class="text-muted">1 week ago</small>
                            </div>
                            <p class="mb-2">"Would appreciate more regular check-ins to discuss project progress and address any roadblocks early in the process."</p>
                            <div class="feedback-rating">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="far fa-star text-muted"></i>
                                <span class="ms-2 text-muted">4.0</span>
                            </div>
                        </div>
                        
                        <div class="feedback-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary">Support & Development</span>
                                <small class="text-muted">2 weeks ago</small>
                            </div>
                            <p class="mb-2">"Thank you for providing opportunities for professional growth. The mentorship has been really valuable for my development."</p>
                            <div class="feedback-rating">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <span class="ms-2 text-muted">5.0</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- View More Feedback -->
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>View All Feedback
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Feedback Analytics -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Feedback Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="feedbackCategoryChart" height="200"></canvas>
                    
                    <h6 class="mt-4 mb-3">Key Insights</h6>
                    <div class="insights-list">
                        <div class="insight-item mb-2 p-2 bg-success bg-opacity-10 rounded">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Strong leadership skills recognized</small>
                        </div>
                        <div class="insight-item mb-2 p-2 bg-warning bg-opacity-10 rounded">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            <small>Communication frequency can improve</small>
                        </div>
                        <div class="insight-item mb-2 p-2 bg-info bg-opacity-10 rounded">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <small>Team development support appreciated</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items & Development -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Action Items
                    </h5>
                </div>
                <div class="card-body">
                    <div class="action-item mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="action1">
                            <label class="form-check-label" for="action1">
                                <strong>Increase team check-in frequency</strong>
                                <br>
                                <small class="text-muted">Schedule weekly team meetings and individual check-ins</small>
                            </label>
                        </div>
                    </div>
                    <div class="action-item mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="action2">
                            <label class="form-check-label" for="action2">
                                <strong>Improve project communication</strong>
                                <br>
                                <small class="text-muted">Establish clear communication channels and expectations</small>
                            </label>
                        </div>
                    </div>
                    <div class="action-item mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="action3" checked>
                            <label class="form-check-label" for="action3">
                                <strong>Continue mentoring support</strong>
                                <br>
                                <small class="text-muted">Maintain current level of developmental support</small>
                            </label>
                        </div>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Add Action Item
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Trend Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="feedbackTrendChart" height="150"></canvas>
                    
                    <div class="trend-insights mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Leadership Rating</span>
                            <span class="badge bg-success">+0.3</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 84%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Communication Rating</span>
                            <span class="badge bg-danger">-0.2</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 68%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Support Rating</span>
                            <span class="badge bg-success">+0.5</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 90%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts and Interactions -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Feedback Category Chart
const categoryCtx = document.getElementById('feedbackCategoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: ['Leadership', 'Communication', 'Support', 'Decision Making', 'Team Building'],
        datasets: [{
            data: [8, 6, 5, 3, 2],
            backgroundColor: ['#dc3545', '#ffc107', '#0dcaf0', '#198754', '#6f42c1']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Feedback Trend Chart
const trendCtx = document.getElementById('feedbackTrendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Average Rating',
            data: [3.8, 4.0, 4.1, 3.9, 4.2, 4.2],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 5
            }
        }
    }
});

// Functions for actions
function exportReport() {
    alert('Export functionality would generate a comprehensive upward feedback report.');
}

function initiateFeedback() {
    alert('New feedback request would be sent to your team members.');
}

// Filter feedback based on selection
document.querySelectorAll('input[name="feedbackView"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // In a real implementation, this would filter the feedback items
        console.log('Filtering feedback:', this.value);
    });
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>