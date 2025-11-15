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
$measurementUnits = $kpiClass->getMeasurementUnits();
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
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4>Company KPIs Management</h4>
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-export me-2"></i>Download Catalog
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/api/kpi_catalog.php?source=db&format=csv&download=1" target="_blank" rel="noopener">
                                <i class="fas fa-database me-2 text-primary"></i>Current KPI Catalog (CSV)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/api/kpi_catalog.php?source=starter&format=csv&download=1" target="_blank" rel="noopener">
                                <i class="fas fa-seedling me-2 text-success"></i>Starter KPI Library (CSV)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/api/kpi_catalog.php?source=starter&format=json" target="_blank" rel="noopener">
                                <i class="fas fa-code me-2 text-muted"></i>Starter KPI Library (JSON)
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
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
                                <label for="category_select" class="form-label">Category</label>
                                <select class="form-select" id="category_select" onchange="handleCategoryChange()">
                                    <option value="">Select existing category...</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                        <?php echo htmlspecialchars($category['category']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="__new__">Create new category...</option>
                                </select>
                                <input type="text" class="form-control mt-2" id="category_new" placeholder="Enter new category" style="display: none;">
                                <input type="hidden" name="category" id="category_final">
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
                                <label for="measurement_unit_select" class="form-label">Measurement Unit</label>
                                <select class="form-select" id="measurement_unit_select" onchange="handleMeasurementUnitChange()">
                                    <option value="">Select existing unit...</option>
                                    <?php foreach ($measurementUnits as $unit): ?>
                                    <option value="<?php echo htmlspecialchars($unit['measurement_unit']); ?>">
                                        <?php echo htmlspecialchars($unit['measurement_unit']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="__new__">Create new unit...</option>
                                </select>
                                <input type="text" class="form-control mt-2" id="measurement_unit_new" placeholder="Enter new measurement unit" style="display: none;">
                                <input type="hidden" name="measurement_unit" id="measurement_unit_final">
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
                <div class="alert alert-light border" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                        <div>
                            <div class="fw-semibold">Need a starting point?</div>
                            <div class="small text-muted">Download the curated KPI catalog or export the current company list to tweak it offline.</div>
                            <div class="mt-2 small">
                                <a href="/api/kpi_catalog.php?source=starter&format=csv&download=1" target="_blank" rel="noopener" class="me-2">
                                    <i class="fas fa-file-csv me-1"></i>Starter catalog (CSV)
                                </a>
                                <a href="/api/kpi_catalog.php?source=starter&format=json" target="_blank" rel="noopener" class="me-2">
                                    <i class="fas fa-code me-1"></i>Starter catalog (JSON)
                                </a>
                                <a href="/api/kpi_catalog.php?source=db&format=csv&download=1" target="_blank" rel="noopener">
                                    <i class="fas fa-database me-1"></i>Current catalog (CSV)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mb-2">Upload a CSV file with the following headers (case-insensitive):</p>
                <ul class="small mb-3">
                    <li><strong>KPI Name</strong> (required)</li>
                    <li><strong>Description</strong> (optional)</li>
                    <li><strong>Measurement Unit</strong> (optional, defaults to <em>count</em>)</li>
                    <li><strong>Category</strong> (required)</li>
                    <li><strong>Target Type</strong> (higher_better, lower_better, target_range)</li>
                </ul>
                <p class="text-muted small mb-3">Duplicate KPI name + category pairs will be updated automatically. Extra columns are ignored.</p>
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="csvFile" accept=".csv" required>
                    </div>
                </form>
                <div id="importFeedback" class="alert alert-info d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importButton" onclick="importKPIs()">Import</button>
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
// Handle category selection in Create modal
function handleCategoryChange() {
    const select = document.getElementById('category_select');
    const newInput = document.getElementById('category_new');
    const finalInput = document.getElementById('category_final');
    
    if (select.value === '__new__') {
        newInput.style.display = 'block';
        newInput.focus();
        finalInput.value = '';
    } else {
        newInput.style.display = 'none';
        finalInput.value = select.value;
    }
}

// Handle measurement unit selection in Create modal
function handleMeasurementUnitChange() {
    const select = document.getElementById('measurement_unit_select');
    const newInput = document.getElementById('measurement_unit_new');
    const finalInput = document.getElementById('measurement_unit_final');
    
    if (select.value === '__new__') {
        newInput.style.display = 'block';
        newInput.focus();
        finalInput.value = '';
    } else {
        newInput.style.display = 'none';
        finalInput.value = select.value;
    }
}

// Update final values when typing in new inputs
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('category_new').addEventListener('input', function() {
        document.getElementById('category_final').value = this.value;
    });
    
    document.getElementById('measurement_unit_new').addEventListener('input', function() {
        document.getElementById('measurement_unit_final').value = this.value;
    });
});

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

function setImportFeedback(message, variant = 'info') {
    const feedback = document.getElementById('importFeedback');
    if (!feedback) return;
    
    feedback.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning', 'alert-danger');
    feedback.classList.add(`alert-${variant}`);
    feedback.innerHTML = message;
}

function safeImportMetric(value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

function formatImportSummary(data) {
    const summary = [
        `<strong>Imported:</strong> ${safeImportMetric(data.imported)}`,
        `<strong>Updated:</strong> ${safeImportMetric(data.updated)}`,
        `<strong>Skipped:</strong> ${safeImportMetric(data.skipped)}`
    ].join('<br>');
    
    if (Array.isArray(data.errors) && data.errors.length) {
        const issues = data.errors.slice(0, 5).map(error => `<li>${error}</li>`).join('');
        return `${summary}<hr class="my-2"><strong>Issues</strong><ul class="mb-0">${issues}</ul>`;
    }
    
    return summary;
}

function importKPIs() {
    const fileInput = document.getElementById('csvFile');
    if (!fileInput || !fileInput.files[0]) {
        setImportFeedback('Please select a CSV file to import.', 'warning');
        return;
    }
    
    const importButton = document.getElementById('importButton');
    const originalButtonLabel = importButton ? importButton.innerHTML : '';
    const restoreButton = () => {
        if (importButton) {
            importButton.disabled = false;
            importButton.innerHTML = originalButtonLabel || 'Import';
        }
    };
    
    if (importButton) {
        importButton.disabled = true;
        importButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing';
    }
    
    setImportFeedback('Uploading and validating KPI catalog...', 'info');
    
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
                const variant = Array.isArray(data.errors) && data.errors.length ? 'warning' : 'success';
                setImportFeedback(formatImportSummary(data), variant);
                setTimeout(() => location.reload(), 1200);
            } else {
                const message = data.message ? `Import failed: ${data.message}` : 'Import failed. Please review the CSV file.';
                setImportFeedback(message, 'danger');
            }
            restoreButton();
        })
        .catch(error => {
            console.error('Error importing KPIs:', error);
            setImportFeedback('Unexpected error importing KPIs. Please try again.', 'danger');
            restoreButton();
        });
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
