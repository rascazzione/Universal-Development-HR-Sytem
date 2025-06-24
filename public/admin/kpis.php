<?php
/**
 * Company KPIs Management
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/CompanyKPI.php';

// Require HR admin authentication
requireAuth();
if (!hasPermission('*')) {
    setFlashMessage('You do not have permission to access this page.', 'error');
    redirect('/dashboard.php');
}

// Initialize classes
$kpiClass = new CompanyKPI();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/kpis.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_kpi':
            try {
                $kpiData = [
                    'kpi_name' => sanitizeInput($_POST['kpi_name']),
                    'kpi_description' => sanitizeInput($_POST['kpi_description']),
                    'measurement_unit' => sanitizeInput($_POST['measurement_unit']),
                    'category' => sanitizeInput($_POST['category']),
                    'target_type' => sanitizeInput($_POST['target_type']),
                    'created_by' => $_SESSION['user_id']
                ];
                
                $kpiClass->createKPI($kpiData);
                setFlashMessage('KPI created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating KPI: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'update_kpi':
            try {
                $kpiId = (int)$_POST['kpi_id'];
                $kpiData = [
                    'kpi_name' => sanitizeInput($_POST['kpi_name']),
                    'kpi_description' => sanitizeInput($_POST['kpi_description']),
                    'measurement_unit' => sanitizeInput($_POST['measurement_unit']),
                    'category' => sanitizeInput($_POST['category']),
                    'target_type' => sanitizeInput($_POST['target_type'])
                ];
                
                $kpiClass->updateKPI($kpiId, $kpiData);
                setFlashMessage('KPI updated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error updating KPI: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_kpi':
            try {
                $kpiId = (int)$_POST['kpi_id'];
                $kpiClass->deleteKPI($kpiId);
                setFlashMessage('KPI deleted successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting KPI: ' . $e->getMessage(), 'error');
            }
            break;
    }
}

// Get data for display
$selectedCategory = $_GET['category'] ?? null;
$kpis = $kpiClass->getKPIs($selectedCategory);
$categories = $kpiClass->getKPICategories();
$editKPI = null;

if (isset($_GET['edit'])) {
    $editKPI = $kpiClass->getKPIById($_GET['edit']);
}

$pageTitle = 'Company KPIs Management';
$pageHeader = true;
$pageDescription = 'Manage company-wide Key Performance Indicators';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Company KPIs Management</h4>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload me-2"></i>Import KPIs
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKPIModal">
                    <i class="fas fa-plus me-2"></i>Create New KPI
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="category" class="form-label">Filter by Category</label>
                        <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                                    <?php echo $selectedCategory === $category['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="/admin/kpis.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- KPIs List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    Key Performance Indicators
                    <?php if ($selectedCategory): ?>
                    <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($selectedCategory); ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($kpis)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No KPIs found</h5>
                    <p class="text-muted">Create your first KPI to get started with performance measurement.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>KPI Name</th>
                                <th>Category</th>
                                <th>Measurement Unit</th>
                                <th>Target Type</th>
                                <th>Created By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kpis as $kpi): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($kpi['kpi_name']); ?></strong>
                                    <?php if ($kpi['kpi_description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($kpi['kpi_description'], 0, 100)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($kpi['category']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($kpi['measurement_unit']); ?></td>
                                <td>
                                    <?php
                                    $targetTypes = [
                                        'higher_better' => ['Higher is Better', 'success'],
                                        'lower_better' => ['Lower is Better', 'warning'],
                                        'target_range' => ['Target Range', 'info']
                                    ];
                                    $typeInfo = $targetTypes[$kpi['target_type']] ?? ['Unknown', 'secondary'];
                                    ?>
                                    <span class="badge bg-<?php echo $typeInfo[1]; ?>"><?php echo $typeInfo[0]; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($kpi['created_by_username'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($kpi['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="editKPI(<?php echo $kpi['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="viewKPIUsage(<?php echo $kpi['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteKPI(<?php echo $kpi['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Create KPI Modal -->
<div class="modal fade" id="createKPIModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New KPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_kpi">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kpi_name" class="form-label">KPI Name</label>
                                <input type="text" class="form-control" id="kpi_name" name="kpi_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       list="categoryList" placeholder="e.g., Sales, Quality, Efficiency">
                                <datalist id="categoryList">
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kpi_description" class="form-label">Description</label>
                        <textarea class="form-control" id="kpi_description" name="kpi_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="measurement_unit" class="form-label">Measurement Unit</label>
                                <input type="text" class="form-control" id="measurement_unit" name="measurement_unit" 
                                       placeholder="e.g., %, $, units, hours">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_type" class="form-label">Target Type</label>
                                <select class="form-select" id="target_type" name="target_type" required>
                                    <option value="higher_better">Higher is Better</option>
                                    <option value="lower_better">Lower is Better</option>
                                    <option value="target_range">Target Range</option>
                                </select>
                                <div class="form-text">
                                    <small>
                                        <strong>Higher is Better:</strong> Sales, productivity, customer satisfaction<br>
                                        <strong>Lower is Better:</strong> Defects, costs, response time<br>
                                        <strong>Target Range:</strong> Budget variance, quality metrics
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit KPI Modal -->
<div class="modal fade" id="editKPIModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit KPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_kpi">
                    <input type="hidden" name="kpi_id" id="edit_kpi_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_kpi_name" class="form-label">KPI Name</label>
                                <input type="text" class="form-control" id="edit_kpi_name" name="kpi_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="edit_category" name="category" 
                                       list="editCategoryList">
                                <datalist id="editCategoryList">
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_kpi_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_kpi_description" name="kpi_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_measurement_unit" class="form-label">Measurement Unit</label>
                                <input type="text" class="form-control" id="edit_measurement_unit" name="measurement_unit">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_target_type" class="form-label">Target Type</label>
                                <select class="form-select" id="edit_target_type" name="target_type" required>
                                    <option value="higher_better">Higher is Better</option>
                                    <option value="lower_better">Lower is Better</option>
                                    <option value="target_range">Target Range</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import KPIs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Upload a CSV file with the following columns:</p>
                <ul>
                    <li>KPI Name</li>
                    <li>Description</li>
                    <li>Measurement Unit</li>
                    <li>Category</li>
                    <li>Target Type (higher_better, lower_better, target_range)</li>
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
                <button type="button" class="btn btn-primary" onclick="importKPIs()">Import</button>
            </div>
        </div>
    </div>
</div>

<!-- KPI Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KPI Usage</h5>
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

<script>
function editKPI(kpiId) {
    // Fetch KPI data and populate edit modal
    fetch(`/api/kpi.php?id=${kpiId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const kpi = data.kpi;
                document.getElementById('edit_kpi_id').value = kpi.id;
                document.getElementById('edit_kpi_name').value = kpi.kpi_name;
                document.getElementById('edit_category').value = kpi.category;
                document.getElementById('edit_kpi_description').value = kpi.kpi_description;
                document.getElementById('edit_measurement_unit').value = kpi.measurement_unit;
                document.getElementById('edit_target_type').value = kpi.target_type;
                
                new bootstrap.Modal(document.getElementById('editKPIModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching KPI data:', error);
            alert('Error loading KPI data');
        });
}

function deleteKPI(kpiId) {
    if (confirm('Are you sure you want to delete this KPI? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_kpi">
            <input type="hidden" name="kpi_id" value="${kpiId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewKPIUsage(kpiId) {
    // Load KPI usage data
    fetch(`/api/kpi_usage.php?id=${kpiId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('usageContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('usageModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching KPI usage:', error);
            alert('Error loading KPI usage data');
        });
}

function importKPIs() {
    const fileInput = document.getElementById('csvFile');
    if (!fileInput.files[0]) {
        alert('Please select a CSV file');
        return;
    }
    
    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('action', 'import_kpis');
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('/api/import_kpis.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully imported ${data.imported} KPIs`);
            location.reload();
        } else {
            alert('Import failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error importing KPIs:', error);
        alert('Error importing KPIs');
    });
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>