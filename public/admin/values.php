<?php
/**
 * Company Values Management
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyValues.php';

// Require HR admin authentication
requireAuth();
if (!hasPermission('*')) {
    setFlashMessage('You do not have permission to access this page.', 'error');
    redirect('/dashboard.php');
}

// Initialize classes
$valuesClass = new CompanyValues();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/values.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_value':
            try {
                $valueData = [
                    'value_name' => sanitizeInput($_POST['value_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                    'created_by' => $_SESSION['user_id']
                ];
                
                $valuesClass->createValue($valueData);
                setFlashMessage('Company value created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating value: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'update_value':
            try {
                $valueId = (int)$_POST['value_id'];
                $valueData = [
                    'value_name' => sanitizeInput($_POST['value_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'sort_order' => (int)($_POST['sort_order'] ?? 0)
                ];
                
                $valuesClass->updateValue($valueId, $valueData);
                setFlashMessage('Company value updated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error updating value: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_value':
            try {
                $valueId = (int)$_POST['value_id'];
                $valuesClass->deleteValue($valueId);
                setFlashMessage('Company value deleted successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting value: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'reorder_values':
            try {
                $valueIds = json_decode($_POST['value_ids'], true);
                if ($valuesClass->reorderValues($valueIds)) {
                    setFlashMessage('Values reordered successfully.', 'success');
                } else {
                    setFlashMessage('Error reordering values.', 'error');
                }
                
            } catch (Exception $e) {
                setFlashMessage('Error reordering values: ' . $e->getMessage(), 'error');
            }
            break;
    }
}

// Get data for display
$values = $valuesClass->getValues();
$editValue = null;

if (isset($_GET['edit'])) {
    $editValue = $valuesClass->getValueById($_GET['edit']);
}

$pageTitle = 'Company Values Management';
$pageHeader = true;
$pageDescription = 'Manage company values and cultural principles';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Company Values Management</h4>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload me-2"></i>Import Values
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createValueModal">
                    <i class="fas fa-plus me-2"></i>Create New Value
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Values Overview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Company Values Overview</h5>
            </div>
            <div class="card-body">
                <?php if (empty($values)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No company values defined</h5>
                    <p class="text-muted">Create your first company value to establish your organizational culture.</p>
                </div>
                <?php else: ?>
                <div class="row" id="valuesContainer">
                    <?php foreach ($values as $value): ?>
                    <div class="col-md-6 col-lg-4 mb-4" data-value-id="<?php echo $value['id']; ?>">
                        <div class="card border-primary h-100 value-card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 text-white">
                                    <i class="fas fa-grip-vertical me-2 drag-handle" style="cursor: move;"></i>
                                    <?php echo htmlspecialchars($value['value_name']); ?>
                                </h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="editValue(<?php echo $value['id']; ?>)">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="viewValueUsage(<?php echo $value['id']; ?>)">
                                            <i class="fas fa-eye me-2"></i>View Usage
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteValue(<?php echo $value['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($value['description']); ?></p>
                                <div class="mt-auto">
                                    <small class="text-muted">
                                        Order: <?php echo $value['sort_order']; ?> | 
                                        Created by: <?php echo htmlspecialchars($value['created_by_username'] ?? 'N/A'); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($value['created_at'], 'M j, Y'); ?>
                                    </small>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewValueBehaviors(<?php echo $value['id']; ?>)">
                                        <i class="fas fa-list me-1"></i>Behaviors
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tip:</strong> Drag and drop the values to reorder them. The order will be saved automatically.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Values Statistics -->
<?php if (!empty($values)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Values Performance Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Value</th>
                                <th>Total Evaluations</th>
                                <th>Average Score</th>
                                <th>High Performers</th>
                                <th>Needs Improvement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $allStats = $valuesClass->getAllValuesStatistics();
                            foreach ($allStats as $stat): 
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($stat['value_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $stat['total_evaluations'] ?? 0; ?></span>
                                </td>
                                <td>
                                    <?php if ($stat['average_score']): ?>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?php echo number_format($stat['average_score'], 1); ?>/5.0</span>
                                        <div class="progress" style="width: 60px; height: 8px;">
                                            <div class="progress-bar" style="width: <?php echo ($stat['average_score'] / 5) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">No data</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stat['total_evaluations'] > 0): ?>
                                    <span class="text-success"><?php echo round((($stat['max_score'] ?? 0) / ($stat['total_evaluations'] ?: 1)) * 100); ?>%</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stat['total_evaluations'] > 0): ?>
                                    <span class="text-warning"><?php echo round((($stat['min_score'] ?? 0) / ($stat['total_evaluations'] ?: 1)) * 100); ?>%</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDetailedStats('<?php echo $stat['value_name']; ?>')">
                                        <i class="fas fa-chart-bar"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Create Value Modal -->
<div class="modal fade" id="createValueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Company Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_value">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="value_name" class="form-label">Value Name</label>
                                <input type="text" class="form-control" id="value_name" name="value_name" required
                                       placeholder="e.g., Integrity, Excellence, Innovation">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="<?php echo count($values) + 1; ?>" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required
                                  placeholder="Describe what this value means and how it should be demonstrated in daily work..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Tip:</strong> Write clear, actionable descriptions that help employees understand how to live this value in their daily work.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Value</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Value Modal -->
<div class="modal fade" id="editValueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Company Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_value">
                    <input type="hidden" name="value_id" id="edit_value_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_value_name" class="form-label">Value Name</label>
                                <input type="text" class="form-control" id="edit_value_name" name="value_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Value</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Value Behaviors Modal -->
<div class="modal fade" id="behaviorsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Behavioral Indicators</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="behaviorsContent">
                <!-- Content loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Value Usage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="usageContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Company Values</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Upload a CSV file with the following columns:</p>
                <ul>
                    <li>Value Name</li>
                    <li>Description</li>
                    <li>Sort Order</li>
                </ul>
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="csvFile" accept=".csv" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="importValues()">Import</button>
            </div>
        </div>
    </div>
</div>

<style>
.value-card {
    transition: transform 0.2s;
}

.value-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    opacity: 0.6;
}

.drag-handle:hover {
    opacity: 1;
}

.sortable-ghost {
    opacity: 0.4;
}

.sortable-chosen {
    transform: rotate(5deg);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Initialize drag and drop sorting
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('valuesContainer');
    if (container) {
        new Sortable(container, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                // Get new order
                const valueIds = Array.from(container.children).map(el => el.dataset.valueId);
                
                // Send to server
                fetch('/admin/values.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'csrf_token': '<?php echo generateCSRFToken(); ?>',
                        'action': 'reorder_values',
                        'value_ids': JSON.stringify(valueIds)
                    })
                })
                .then(response => response.text())
                .then(data => {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        <i class="fas fa-check me-2"></i>Values reordered successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.row'));
                    
                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                })
                .catch(error => {
                    console.error('Error reordering values:', error);
                    alert('Error reordering values');
                });
            }
        });
    }
});

function editValue(valueId) {
    // Fetch value data and populate edit modal
    fetch(`/api/value.php?id=${valueId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const value = data.value;
                document.getElementById('edit_value_id').value = value.id;
                document.getElementById('edit_value_name').value = value.value_name;
                document.getElementById('edit_description').value = value.description;
                document.getElementById('edit_sort_order').value = value.sort_order;
                
                new bootstrap.Modal(document.getElementById('editValueModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching value data:', error);
            alert('Error loading value data');
        });
}

function deleteValue(valueId) {
    if (confirm('Are you sure you want to delete this company value? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_value">
            <input type="hidden" name="value_id" value="${valueId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewValueBehaviors(valueId) {
    // Load behavioral indicators
    fetch(`/api/value_behaviors.php?id=${valueId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('behaviorsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('behaviorsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching behaviors:', error);
            alert('Error loading behavioral indicators');
        });
}

function viewValueUsage(valueId) {
    // Load value usage data
    fetch(`/api/value_usage.php?id=${valueId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('usageContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('usageModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching value usage:', error);
            alert('Error loading value usage data');
        });
}

function viewDetailedStats(valueName) {
    // Redirect to detailed statistics page
    window.open(`/reports/value_statistics.php?value=${encodeURIComponent(valueName)}`, '_blank');
}

function importValues() {
    const fileInput = document.getElementById('csvFile');
    if (!fileInput.files[0]) {
        alert('Please select a CSV file');
        return;
    }
    
    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('action', 'import_values');
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('/api/import_values.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully imported ${data.imported} values`);
            location.reload();
        } else {
            alert('Import failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error importing values:', error);
        alert('Error importing values');
    });
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>