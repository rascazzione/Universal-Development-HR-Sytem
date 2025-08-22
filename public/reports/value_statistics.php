<?php
/**
 * Value Statistics Report
 * Detailed statistics for a specific company value
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyValues.php';

// Require authentication
requireAuth();

// Check if user has permission to view reports
$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['hr_admin', 'manager', 'employee'])) {
    header('Location: /dashboard.php');
    exit;
}

// Get value name from query parameter
$valueName = $_GET['value'] ?? '';

if (empty($valueName)) {
    setFlashMessage('No value specified for report.', 'error');
    header('Location: /admin/values.php');
    exit;
}

// Initialize classes
$valuesClass = new CompanyValues();

// Get value details
$values = $valuesClass->getValues();
$currentValue = null;
foreach ($values as $value) {
    if ($value['value_name'] === $valueName) {
        $currentValue = $value;
        break;
    }
}

if (!$currentValue) {
    setFlashMessage('Value not found.', 'error');
    header('Location: /admin/values.php');
    exit;
}

// Get statistics for this value
$stats = $valuesClass->getValueStatistics($currentValue['id']);

// Get usage data
$usage = $valuesClass->getValueUsage($currentValue['id']);

// Get behavioral indicators
$behaviors = $valuesClass->getValueBehaviors($currentValue['id']);

$pageTitle = 'Value Statistics: ' . htmlspecialchars($valueName);
$pageHeader = true;
$pageDescription = 'Detailed statistics and usage information for the company value: ' . htmlspecialchars($valueName);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-chart-bar"></i> Value Statistics Report</h1>
                <a href="/admin/values.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Values
                </a>
            </div>
            
            <!-- Value Overview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-heart text-danger"></i> <?php echo htmlspecialchars($currentValue['value_name']); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars($currentValue['description']); ?></p>
                    <div class="row">
                        <div class="col-md-3">
                            <small class="text-muted">Created by:</small>
                            <div><?php echo htmlspecialchars($currentValue['created_by_username'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Sort Order:</small>
                            <div><?php echo $currentValue['sort_order']; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Created:</small>
                            <div><?php echo formatDate($currentValue['created_at'], 'M j, Y'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Status:</small>
                            <div><span class="badge bg-<?php echo $currentValue['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $currentValue['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5><?php echo $stats['total_evaluations'] ?? 0; ?></h5>
                            <small>Total Evaluations</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5><?php echo number_format($stats['average_score'] ?? 0, 1); ?>/5.0</h5>
                            <small>Average Score</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5><?php echo $stats['high_performers'] ?? 0; ?></h5>
                            <small>High Performers</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h5><?php echo $stats['low_performers'] ?? 0; ?></h5>
                            <small>Needs Improvement</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Detailed Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Score Distribution</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Maximum Score:</td>
                                    <td class="text-end"><?php echo number_format($stats['max_score'] ?? 0, 1); ?>/5.0</td>
                                </tr>
                                <tr>
                                    <td>Minimum Score:</td>
                                    <td class="text-end"><?php echo number_format($stats['min_score'] ?? 0, 1); ?>/5.0</td>
                                </tr>
                                <tr>
                                    <td>Average Score:</td>
                                    <td class="text-end"><?php echo number_format($stats['average_score'] ?? 0, 1); ?>/5.0</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Performance Indicators</h6>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($stats['high_performers'] ?? 0) * 10); ?>%">
                                    High Performers
                                </div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-warning" style="width: <?php echo min(100, ($stats['low_performers'] ?? 0) * 10); ?>%">
                                    Needs Improvement
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Behavioral Indicators -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Behavioral Indicators</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <?php foreach ($behaviors as $behavior): ?>
                        <li><?php echo htmlspecialchars($behavior); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Value Usage -->
            <?php if (!empty($usage)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-briefcase"></i> Value Usage in Job Templates</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Position Title</th>
                                    <th>Department</th>
                                    <th>Weight Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usage as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['department']); ?></td>
                                    <td><?php echo number_format($item['weight_percentage'], 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
               </div>
           </div>
       </div>
       
       <?php include __DIR__ . '/../../templates/footer.php'; ?>
               </div>
           </div>
           <?php else: ?>
           <div class="card">
               <div class="card-header">
                   <h5 class="mb-0"><i class="fas fa-briefcase"></i> Value Usage</h5>
               </div>
               <div class="card-body">
                   <p class="text-muted">This value is not currently assigned to any job templates.</p>
               </div>
           </div>
           <?php endif; ?>
       </div>
   </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
