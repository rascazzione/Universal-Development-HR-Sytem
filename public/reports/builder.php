<?php
/**
 * Custom Report Builder Interface
 * Phase 3: Advanced Features - Report Builder
 * Growth Evidence System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/ReportGenerator.php';

// Require authentication
requireAuth();

// Check if user has permission to build reports
$userRole = $_SESSION['role'];
if (!in_array($userRole, ['hr_admin', 'manager'])) {
    header('Location: /dashboard.php');
    exit;
}

$reportGenerator = new ReportGenerator();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'generate_report':
                $reportType = $_POST['report_type'] ?? '';
                $parameters = $_POST['parameters'] ?? [];
                
                // Generate the report
                $reportData = $reportGenerator->generateReportByType($reportType, $parameters);
                
                // Store report data in session for display
                $_SESSION['generated_report'] = $reportData;
                
                $message = "Report generated successfully.";
                $messageType = 'success';
                break;
                
            case 'export_report':
                if (isset($_SESSION['generated_report'])) {
                    $format = $_POST['export_format'] ?? 'pdf';
                    $reportData = $_SESSION['generated_report'];
                    
                    if ($format === 'pdf') {
                        $filePath = $reportGenerator->exportToPDF($reportData);
                    } else {
                        $filePath = $reportGenerator->exportToExcel($reportData);
                    }
                    
                    // Redirect to download
                    header("Location: /api/reports.php?action=download&file=" . urlencode(basename($filePath)));
                    exit;
                } else {
                    $message = "No report to export. Please generate a report first.";
                    $messageType = 'error';
                }
                break;
                
            case 'schedule_report':
                $scheduleData = [
                    'report_name' => $_POST['schedule_name'] ?? '',
                    'report_type' => $_POST['report_type'] ?? '',
                    'parameters' => $_POST['parameters'] ?? [],
                    'recipients' => explode(',', $_POST['recipients'] ?? ''),
                    'schedule_frequency' => $_POST['schedule_frequency'] ?? '',
                    'schedule_day_of_week' => $_POST['schedule_day_of_week'] ?? null,
                    'schedule_day_of_month' => $_POST['schedule_day_of_month'] ?? null,
                    'created_by' => $_SESSION['user_id']
                ];
                
                $scheduleId = $reportGenerator->scheduleReport($scheduleData);
                
                $message = "Report scheduled successfully.";
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get available employees and managers for filters
$employees = [];
$managers = [];

if ($userRole === 'manager') {
    $managerId = $_SESSION['employee_id'];
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE manager_id = ? AND active = TRUE", [$managerId]);
} else {
    $employees = fetchAll("SELECT employee_id, first_name, last_name FROM employees WHERE active = TRUE ORDER BY first_name, last_name");
}

if ($userRole === 'hr_admin') {
    $managers = fetchAll("SELECT DISTINCT e.employee_id, e.first_name, e.last_name 
                         FROM employees e 
                         JOIN growth_evidence_entries gee ON e.employee_id = gee.manager_id 
                         ORDER BY e.first_name, e.last_name");
}

// Get report templates
$reportTemplates = [
    'evidence_summary' => [
        'name' => 'Evidence Summary Report',
        'description' => 'Comprehensive overview of evidence entries with statistics and trends',
        'icon' => 'fas fa-chart-bar'
    ],
    'performance_trends' => [
        'name' => 'Performance Trends Report',
        'description' => 'Analysis of performance trends over time for specific employees',
        'icon' => 'fas fa-chart-line'
    ],
    'manager_overview' => [
        'name' => 'Manager Overview Report',
        'description' => 'Team performance overview for managers',
        'icon' => 'fas fa-users'
    ],
    'custom' => [
        'name' => 'Custom Report',
        'description' => 'Build your own custom report with specific criteria',
        'icon' => 'fas fa-cogs'
    ]
];

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-chart-pie"></i> Report Builder</h1>
                <div>
                    <a href="generate.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Report History
                    </a>
                    <a href="/evidence/manage.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cogs"></i> Evidence Management
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Report Builder Panel -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-hammer"></i> Build Your Report</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="reportBuilderForm">
                                <input type="hidden" name="action" value="generate_report">
                                
                                <!-- Step 1: Select Report Type -->
                                <div class="step-section" id="step1">
                                    <h6 class="text-primary mb-3"><i class="fas fa-step-forward"></i> Step 1: Select Report Type</h6>
                                    <div class="row">
                                        <?php foreach ($reportTemplates as $type => $template): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card report-template" data-type="<?php echo $type; ?>">
                                                    <div class="card-body text-center">
                                                        <i class="<?php echo $template['icon']; ?> fa-2x text-primary mb-2"></i>
                                                        <h6 class="card-title"><?php echo $template['name']; ?></h6>
                                                        <p class="card-text small text-muted"><?php echo $template['description']; ?></p>
                                                        <input type="radio" name="report_type" value="<?php echo $type; ?>" class="form-check-input" style="display: none;">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Step 2: Configure Parameters -->
                                <div class="step-section" id="step2" style="display: none;">
                                    <h6 class="text-primary mb-3"><i class="fas fa-step-forward"></i> Step 2: Configure Parameters</h6>
                                    
                                    <!-- Common Parameters -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="parameters[start_date]" id="start_date" value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="parameters[end_date]" id="end_date" value="<?php echo date('Y-m-t'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="employee_filter" class="form-label">Employee</label>
                                            <select class="form-select" name="parameters[employee_id]" id="employee_filter">
                                                <option value="">All Employees</option>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?php echo $employee['employee_id']; ?>">
                                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php if ($userRole === 'hr_admin'): ?>
                                        <div class="col-md-3">
                                            <label for="manager_filter" class="form-label">Manager</label>
                                            <select class="form-select" name="parameters[manager_id]" id="manager_filter">
                                                <option value="">All Managers</option>
                                                <?php foreach ($managers as $manager): ?>
                                                    <option value="<?php echo $manager['employee_id']; ?>">
                                                        <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Report-Specific Parameters -->
                                    <div id="specificParameters">
                                        <!-- Dynamic content based on report type -->
                                    </div>
                                </div>

                                <!-- Step 3: Output Options -->
                                <div class="step-section" id="step3" style="display: none;">
                                    <h6 class="text-primary mb-3"><i class="fas fa-step-forward"></i> Step 3: Output Options</h6>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="report_title" class="form-label">Report Title</label>
                                            <input type="text" class="form-control" name="parameters[title]" id="report_title" placeholder="Custom Report Title">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="include_charts" class="form-label">Include Charts</label>
                                            <select class="form-select" name="parameters[include_charts]" id="include_charts">
                                                <option value="true">Yes</option>
                                                <option value="false">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="detail_level" class="form-label">Detail Level</label>
                                            <select class="form-select" name="parameters[detail_level]" id="detail_level">
                                                <option value="summary">Summary</option>
                                                <option value="detailed">Detailed</option>
                                                <option value="comprehensive">Comprehensive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Navigation Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <div class="ms-auto">
                                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                                            Next <i class="fas fa-arrow-right"></i>
                                        </button>
                                        <button type="submit" class="btn btn-success" id="generateBtn" style="display: none;">
                                            <i class="fas fa-play"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Panel -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateQuickReport('evidence_summary', 'last_30_days')">
                                    <i class="fas fa-chart-bar"></i> Evidence Summary (Last 30 Days)
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="generateQuickReport('performance_trends', 'current_quarter')">
                                    <i class="fas fa-chart-line"></i> Performance Trends (Current Quarter)
                                </button>
                                <?php if ($userRole === 'hr_admin'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="generateQuickReport('manager_overview', 'current_month')">
                                    <i class="fas fa-users"></i> Manager Overview (Current Month)
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Report Panel -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-clock"></i> Schedule Report</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="scheduleForm">
                                <input type="hidden" name="action" value="schedule_report">
                                <input type="hidden" name="report_type" id="schedule_report_type">
                                <input type="hidden" name="parameters" id="schedule_parameters">
                                
                                <div class="mb-3">
                                    <label for="schedule_name" class="form-label">Schedule Name</label>
                                    <input type="text" class="form-control" name="schedule_name" id="schedule_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="recipients" class="form-label">Recipients (Email)</label>
                                    <textarea class="form-control" name="recipients" id="recipients" rows="2" placeholder="email1@company.com, email2@company.com"></textarea>
                                    <div class="form-text">Separate multiple emails with commas</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="schedule_frequency" class="form-label">Frequency</label>
                                    <select class="form-select" name="schedule_frequency" id="schedule_frequency" required>
                                        <option value="">Select Frequency</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="schedule_day_options" style="display: none;">
                                    <label for="schedule_day" class="form-label">Day</label>
                                    <select class="form-select" name="schedule_day_of_week" id="schedule_day_of_week" style="display: none;">
                                        <option value="1">Monday</option>
                                        <option value="2">Tuesday</option>
                                        <option value="3">Wednesday</option>
                                        <option value="4">Thursday</option>
                                        <option value="5">Friday</option>
                                        <option value="6">Saturday</option>
                                        <option value="0">Sunday</option>
                                    </select>
                                    <select class="form-select" name="schedule_day_of_month" id="schedule_day_of_month" style="display: none;">
                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-warning btn-sm w-100" disabled id="scheduleBtn">
                                    <i class="fas fa-calendar-plus"></i> Schedule Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generated Report Display -->
            <?php if (isset($_SESSION['generated_report'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Generated Report</h5>
                            <div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="export_report">
                                    <select name="export_format" class="form-select form-select-sm d-inline-block w-auto me-2">
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php 
                        $reportData = $_SESSION['generated_report'];
                        echo "<h6>{$reportData['title']}</h6>";
                        echo "<p class='text-muted'>Generated: {$reportData['generated_at']}</p>";
                        
                        if (isset($reportData['summary'])) {
                            echo "<div class='row mb-3'>";
                            foreach ($reportData['summary'] as $key => $value) {
                                if (is_numeric($value)) {
                                    echo "<div class='col-md-3'>";
                                    echo "<div class='card bg-light'>";
                                    echo "<div class='card-body text-center'>";
                                    echo "<h5>" . number_format($value) . "</h5>";
                                    echo "<small>" . ucwords(str_replace('_', ' ', $key)) . "</small>";
                                    echo "</div></div></div>";
                                }
                            }
                            echo "</div>";
                        }
                        
                        // Display charts if available
                        if (isset($reportData['charts'])) {
                            echo "<div class='row'>";
                            foreach ($reportData['charts'] as $chartName => $chartData) {
                                echo "<div class='col-md-6 mb-3'>";
                                echo "<canvas id='chart_" . $chartName . "' width='400' height='200'></canvas>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
                
                <script>
                // Render charts if Chart.js is available
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if (isset($reportData['charts'])): ?>
                        <?php foreach ($reportData['charts'] as $chartName => $chartData): ?>
                            if (typeof Chart !== 'undefined') {
                                const ctx_<?php echo $chartName; ?> = document.getElementById('chart_<?php echo $chartName; ?>').getContext('2d');
                                new Chart(ctx_<?php echo $chartName; ?>, {
                                    type: 'bar',
                                    data: {
                                        labels: <?php echo json_encode($chartData['labels']); ?>,
                                        datasets: [{
                                            label: '<?php echo ucwords(str_replace('_', ' ', $chartName)); ?>',
                                            data: <?php echo json_encode($chartData['data']); ?>,
                                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                            borderColor: 'rgba(54, 162, 235, 1)',
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            }
                        <?php endforeach; ?>
                    <?php endif; ?>
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Report Builder JavaScript
let currentStep = 1;
const totalSteps = 3;

document.addEventListener('DOMContentLoaded', function() {
    // Report template selection
    document.querySelectorAll('.report-template').forEach(template => {
        template.addEventListener('click', function() {
            // Remove active class from all templates
            document.querySelectorAll('.report-template').forEach(t => t.classList.remove('border-primary', 'bg-light'));
            
            // Add active class to selected template
            this.classList.add('border-primary', 'bg-light');
            
            // Check the radio button
            this.querySelector('input[type="radio"]').checked = true;
            
            // Update specific parameters
            updateSpecificParameters(this.dataset.type);
            
            // Enable next button
            document.getElementById('nextBtn').disabled = false;
        });
    });
    
    // Schedule frequency change
    document.getElementById('schedule_frequency').addEventListener('change', function() {
        const dayOptions = document.getElementById('schedule_day_options');
        const dayOfWeek = document.getElementById('schedule_day_of_week');
        const dayOfMonth = document.getElementById('schedule_day_of_month');
        
        dayOptions.style.display = 'none';
        dayOfWeek.style.display = 'none';
        dayOfMonth.style.display = 'none';
        
        if (this.value === 'weekly') {
            dayOptions.style.display = 'block';
            dayOfWeek.style.display = 'block';
        } else if (this.value === 'monthly' || this.value === 'quarterly') {
            dayOptions.style.display = 'block';
            dayOfMonth.style.display = 'block';
        }
    });
});

function changeStep(direction) {
    const currentStepElement = document.getElementById(`step${currentStep}`);
    currentStepElement.style.display = 'none';
    
    currentStep += direction;
    
    if (currentStep < 1) currentStep = 1;
    if (currentStep > totalSteps) currentStep = totalSteps;
    
    const newStepElement = document.getElementById(`step${currentStep}`);
    newStepElement.style.display = 'block';
    
    // Update navigation buttons
    document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-block' : 'none';
    document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'inline-block' : 'none';
    document.getElementById('generateBtn').style.display = currentStep === totalSteps ? 'inline-block' : 'none';
    
    // Enable schedule button on final step
    document.getElementById('scheduleBtn').disabled = currentStep !== totalSteps;
    
    if (currentStep === totalSteps) {
        // Copy report configuration to schedule form
        const reportType = document.querySelector('input[name="report_type"]:checked')?.value;
        const formData = new FormData(document.getElementById('reportBuilderForm'));
        const parameters = {};
        
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('parameters[')) {
                const paramKey = key.replace('parameters[', '').replace(']', '');
                parameters[paramKey] = value;
            }
        }
        
        document.getElementById('schedule_report_type').value = reportType || '';
        document.getElementById('schedule_parameters').value = JSON.stringify(parameters);
    }
}

function updateSpecificParameters(reportType) {
    const container = document.getElementById('specificParameters');
    let html = '';
    
    switch (reportType) {
        case 'evidence_summary':
            html = `
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="dimension_filter" class="form-label">Dimension</label>
                        <select class="form-select" name="parameters[dimension]" id="dimension_filter">
                            <option value="">All Dimensions</option>
                            <option value="responsibilities">Responsibilities</option>
                            <option value="kpis">KPIs</option>
                            <option value="competencies">Competencies</option>
                            <option value="values">Values</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="min_rating" class="form-label">Minimum Rating</label>
                        <select class="form-select" name="parameters[min_rating]" id="min_rating">
                            <option value="">Any Rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="include_archived" class="form-label">Include Archived</label>
                        <select class="form-select" name="parameters[include_archived]" id="include_archived">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'performance_trends':
            html = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This report requires an employee to be selected above.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="trend_period" class="form-label">Trend Period</label>
                        <select class="form-select" name="parameters[trend_period]" id="trend_period">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="comparison_type" class="form-label">Comparison Type</label>
                        <select class="form-select" name="parameters[comparison_type]" id="comparison_type">
                            <option value="period_over_period">Period over Period</option>
                            <option value="year_over_year">Year over Year</option>
                            <option value="baseline">Against Baseline</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'manager_overview':
            html = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This report shows team performance for the selected manager.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="include_individual_summaries" class="form-label">Include Individual Summaries</label>
                        <select class="form-select" name="parameters[include_individual_summaries]" id="include_individual_summaries">
                            <option value="true">Yes</option>
                            <option value="false">No</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="team_comparison" class="form-label">Team Comparison</label>
                        <select class="form-select" name="parameters[team_comparison]" id="team_comparison">
                            <option value="false">No Comparison</option>
                            <option value="department">Department Average</option>
                            <option value="company">Company Average</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'custom':
            html = `
                <div class="row g-3">
                    <div class="col-12">
                        <label for="custom_title" class="form-label">Custom Report Title</label>
                        <input type="text" class="form-control" name="parameters[custom_title]" id="custom_title" placeholder="Enter custom report title">
                    </div>
                    <div class="col-md-6">
                        <label for="data_source" class="form-label">Data Source</label>
                        <select class="form-select" name="parameters[data_source]" id="data_source">
                            <option value="evidence_entries">Evidence Entries</option>
                            <option value="evaluations">Evaluations</option>
                            <option value="combined">Combined Data</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="aggregation_type" class="form-label">Aggregation</label>
                        <select class="form-select" name="parameters[aggregation_type]" id="aggregation_type">
                            <option value="count">Count</option>
                            <option value="average">Average</option>
                            <option value="sum">Sum</option>
                            <option value="min_max">Min/Max</option>
                        </select>
                    </div>
                </div>
            `;
            break;
    }
    
    container.innerHTML = html;
}

function generateQuickReport(reportType, period) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Set action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput
.value = 'generate_report';
    form.appendChild(actionInput);
    
    // Set report type
    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'report_type';
    typeInput.value = reportType;
    form.appendChild(typeInput);
    
    // Set parameters based on period
    const now = new Date();
    let startDate, endDate;
    
    switch (period) {
        case 'last_30_days':
            startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            endDate = now;
            break;
        case 'current_quarter':
            const quarter = Math.floor(now.getMonth() / 3);
            startDate = new Date(now.getFullYear(), quarter * 3, 1);
            endDate = new Date(now.getFullYear(), quarter * 3 + 3, 0);
            break;
        case 'current_month':
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            break;
    }
    
    const startDateInput = document.createElement('input');
    startDateInput.type = 'hidden';
    startDateInput.name = 'parameters[start_date]';
    startDateInput.value = startDate.toISOString().split('T')[0];
    form.appendChild(startDateInput);
    
    const endDateInput = document.createElement('input');
    endDateInput.type = 'hidden';
    endDateInput.name = 'parameters[end_date]';
    endDateInput.value = endDate.toISOString().split('T')[0];
    form.appendChild(endDateInput);
    
    // Add manager_id for manager overview if current user is manager
    if (reportType === 'manager_overview' && '<?php echo $userRole; ?>' === 'manager') {
        const managerInput = document.createElement('input');
        managerInput.type = 'hidden';
        managerInput.name = 'parameters[manager_id]';
        managerInput.value = '<?php echo $_SESSION['employee_id'] ?? ''; ?>';
        form.appendChild(managerInput);
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Clear generated report from session when page loads
window.addEventListener('beforeunload', function() {
    fetch('/api/reports.php?action=clear_session', {method: 'POST'});
});
</script>

<!-- Include Chart.js for report visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php 
// Clear the generated report from session after displaying
if (isset($_SESSION['generated_report'])) {
    unset($_SESSION['generated_report']);
}

include __DIR__ . '/../../templates/footer.php'; 
?>