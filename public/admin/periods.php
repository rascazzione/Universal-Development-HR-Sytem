<?php
/**
 * Evaluation Periods Management Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Require HR Admin access
requireAuth();
if (!isHRAdmin()) {
    redirectTo('/dashboard.php');
}

$pageTitle = 'Manage Evaluation Periods';
$pageHeader = true;
$pageDescription = 'Create and manage evaluation periods';

// Initialize EvaluationPeriod class
$periodClass = new EvaluationPeriod();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $periodClass->createPeriod([
                    'period_name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'] ?? 'draft'
                ]);
                
                if ($result) {
                    setFlashMessage('Period created successfully!', 'success');
                } else {
                    setFlashMessage('Failed to create period. Please try again.', 'error');
                }
                break;
                
            case 'update_status':
                $result = $periodClass->updatePeriod($_POST['period_id'], ['status' => $_POST['new_status']]);
                
                if ($result) {
                    setFlashMessage('Period status updated successfully!', 'success');
                } else {
                    setFlashMessage('Failed to update period status.', 'error');
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        redirect('/admin/periods.php');
    }
}

// Get all periods
$periodsData = $periodClass->getPeriods(1, 100); // Get first 100 periods
$periods = $periodsData['periods'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Evaluation Periods</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                    <i class="fas fa-plus me-2"></i>Create New Period
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($periods)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No evaluation periods found.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                        <i class="fas fa-plus me-2"></i>Create First Period
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Period Name</th>
                                <th>Description</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Evaluations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periods as $period): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($period['period_name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($period['description'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($period['start_date']); ?></td>
                                <td><?php echo formatDate($period['end_date']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'draft' => 'secondary',
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'archived' => 'dark'
                                    ][$period['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($period['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo $period['evaluation_count'] ?? 0; ?> evaluations
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="viewPeriod(<?php echo $period['period_id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="editPeriod(<?php echo $period['period_id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($period['status'] !== 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="period_id" value="<?php echo $period['period_id']; ?>">
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="btn btn-outline-success" title="Activate Period">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
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
</div>

<!-- Create Period Modal -->
<div class="modal fade" id="createPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Evaluation Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Period Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="form-text">e.g., "Q1 2025 Performance Review"</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date *</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewPeriod(periodId) {
    // Implement view period functionality
    alert('View period functionality - to be implemented');
}

function editPeriod(periodId) {
    // Implement edit period functionality
    alert('Edit period functionality - to be implemented');
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>