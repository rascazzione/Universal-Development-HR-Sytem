<?php
/**
 * Employee List Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';

// Require authentication and HR/Manager access
requireAuth();
if (!isHRAdmin() && !isManager()) {
    redirect('/dashboard.php');
}

$pageTitle = 'All Employees';
$pageHeader = true;
$pageDescription = 'Manage and view all employees in the system';

// Initialize Employee class
$employeeClass = new Employee();

// Get filter parameters
$showInactive = isset($_GET['show_inactive']) ? true : false;

// Get all employees with filter
$filters = [];
if ($showInactive) {
    // We need to modify the query to show all employees, not just active ones
    // For now, let's get all employees including inactive ones
    $employeesData = $employeeClass->getAllEmployees(1, 100); // We'll create this method
} else {
    $employeesData = $employeeClass->getEmployees(1, 100); // Get first 100 active employees
}
$employees = $employeesData['employees'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Employee List</h5>
                    <?php if (isHRAdmin()): ?>
                    <div class="btn-group">
                        <!-- Export Dropdown -->
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportEmployees('basic')">
                                    <i class="fas fa-file-csv me-2"></i>Basic (CSV)
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportEmployees('complete')">
                                    <i class="fas fa-file-archive me-2"></i>Complete (ZIP)
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/api/employees/template.php">
                                    <i class="fas fa-file-download me-2"></i>Download Template
                                </a></li>
                            </ul>
                        </div>
                        
                        <!-- Import Button -->
                        <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-upload me-2"></i>Import
                        </button>
                        
                        <!-- Add Employee Button -->
                        <a href="/employees/add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Employee
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showInactive"
                               <?php echo $showInactive ? 'checked' : ''; ?>
                               onchange="window.location.href = this.checked ? '?show_inactive=1' : '?';">
                        <label class="form-check-label" for="showInactive">
                            Show inactive employees
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($employees)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No employees found.</p>
                    <?php if (isHRAdmin()): ?>
                    <a href="/employees/add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add First Employee
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['employee_number']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <div class="avatar-title bg-primary rounded-circle">
                                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($employee['manager_first_name']): ?>
                                        <?php echo htmlspecialchars($employee['manager_first_name'] . ' ' . $employee['manager_last_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No Manager</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $employee['active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $employee['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/employees/view.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isHRAdmin()): ?>
                                        <a href="/employees/edit.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="/evaluation/create.php?employee_id=<?php echo $employee['employee_id']; ?>" class="btn btn-outline-success" title="Create Evaluation">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="importStep1">
                    <h6>Step 1: Upload CSV File</h6>
                    <p class="text-muted">Upload a CSV file with employee data. <a href="/api/employees/template.php">Download template</a> if you need an example format.</p>
                    
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                            <div class="form-text">Maximum file size: 10MB</div>
                        </div>
                    </form>
                </div>
                
                <div id="importStep2" style="display: none;">
                    <h6>Step 2: Validation Results</h6>
                    <div id="validationResults"></div>
                </div>
                
                <div id="importStep3" style="display: none;">
                    <h6>Step 3: Import Complete</h6>
                    <div id="importResults"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="validateBtn" onclick="validateImport()">Validate</button>
                <button type="button" class="btn btn-success" id="importBtn" onclick="performImport()" style="display: none;">Import</button>
                <button type="button" class="btn btn-primary" id="newImportBtn" onclick="resetImport()" style="display: none;">Import Another File</button>
            </div>
        </div>
    </div>
</div>

<script>
// Export functionality
function exportEmployees(type) {
    const includeInactive = document.getElementById('showInactive').checked;
    let url = `/api/employees/export.php?type=${type}`;
    
    if (includeInactive) {
        url += '&include_inactive=1';
    }
    
    if (type === 'basic') {
        // For basic export, directly download the CSV
        window.location.href = url;
    } else {
        // For complete export, show loading and handle ZIP download
        showLoading('Preparing export...');
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    // Download the ZIP file
                    window.location.href = data.download_url;
                    showAlert('success', `Export complete! Files included: ${data.files_included.join(', ')}`);
                } else {
                    showAlert('danger', 'Export failed: ' + data.error);
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('danger', 'Export failed: ' + error.message);
            });
    }
}

// Import functionality
let validationData = null;

function validateImport() {
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('warning', 'Please select a CSV file to upload.');
        return;
    }
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('action', 'validate');
    
    showLoading('Validating CSV file...');
    document.getElementById('validateBtn').disabled = true;
    
    fetch('/api/employees/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        document.getElementById('validateBtn').disabled = false;
        
        if (data.success) {
            validationData = data.validation_result;
            showValidationResults(data.validation_result);
            
            // Move to step 2
            document.getElementById('importStep1').style.display = 'none';
            document.getElementById('importStep2').style.display = 'block';
            document.getElementById('validateBtn').style.display = 'none';
            
            if (data.validation_result.valid_count > 0) {
                document.getElementById('importBtn').style.display = 'inline-block';
            }
        } else {
            showAlert('danger', 'Validation failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        document.getElementById('validateBtn').disabled = false;
        showAlert('danger', 'Validation failed: ' + error.message);
    });
}

function showValidationResults(results) {
    let html = `
        <div class="alert alert-info">
            <strong>Validation Summary:</strong><br>
            Total rows: ${results.total_rows}<br>
            Valid rows: ${results.valid_count}<br>
            Errors: ${results.error_count}
        </div>
    `;
    
    if (results.errors.length > 0) {
        html += '<div class="alert alert-warning"><strong>Errors found:</strong></div>';
        html += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
        html += '<table class="table table-sm table-striped">';
        html += '<thead><tr><th>Row</th><th>Employee</th><th>Errors</th></tr></thead><tbody>';
        
        results.errors.forEach(error => {
            html += `<tr>
                <td>${error.row}</td>
                <td>${error.data.first_name || ''} ${error.data.last_name || ''} (${error.data.employee_number || ''})</td>
                <td><ul class="mb-0">`;
            
            error.errors.forEach(err => {
                html += `<li class="text-danger">${err}</li>`;
            });
            
            html += '</ul></td></tr>';
        });
        
        html += '</tbody></table></div>';
    }
    
    if (results.valid_count > 0) {
        html += `<div class="alert alert-success">
            <strong>${results.valid_count} employees ready to import:</strong><br>
            <small>Click "Import" to proceed with importing the valid employees.</small>
        </div>`;
    }
    
    document.getElementById('validationResults').innerHTML = html;
}

function performImport() {
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('action', 'import');
    
    showLoading('Importing employees...');
    document.getElementById('importBtn').disabled = true;
    
    fetch('/api/employees/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        document.getElementById('importBtn').disabled = false;
        
        if (data.success) {
            showImportResults(data.import_result);
            
            // Move to step 3
            document.getElementById('importStep2').style.display = 'none';
            document.getElementById('importStep3').style.display = 'block';
            document.getElementById('importBtn').style.display = 'none';
            document.getElementById('newImportBtn').style.display = 'inline-block';
            
            // Refresh the page after a delay to show updated employee list
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            showAlert('danger', 'Import failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        document.getElementById('importBtn').disabled = false;
        showAlert('danger', 'Import failed: ' + error.message);
    });
}

function showImportResults(results) {
    let html = `
        <div class="alert alert-success">
            <h6>Import Successful!</h6>
            <ul class="mb-0">
                <li>Created: ${results.created} employees</li>
                <li>Updated: ${results.updated} employees</li>
                <li>Total processed: ${results.total_processed}</li>
            </ul>
        </div>
    `;
    
    if (results.errors.length > 0) {
        html += '<div class="alert alert-warning"><strong>Some errors occurred:</strong></div>';
        html += '<ul>';
        results.errors.forEach(error => {
            html += `<li>${error.employee_number}: ${error.error}</li>`;
        });
        html += '</ul>';
    }
    
    html += '<p class="text-muted">The page will refresh automatically to show the updated employee list.</p>';
    
    document.getElementById('importResults').innerHTML = html;
}

function resetImport() {
    // Reset the modal to step 1
    document.getElementById('importStep1').style.display = 'block';
    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importStep3').style.display = 'none';
    
    document.getElementById('validateBtn').style.display = 'inline-block';
    document.getElementById('importBtn').style.display = 'none';
    document.getElementById('newImportBtn').style.display = 'none';
    
    // Clear form
    document.getElementById('importForm').reset();
    document.getElementById('validationResults').innerHTML = '';
    document.getElementById('importResults').innerHTML = '';
    
    validationData = null;
}

// Utility functions
function showLoading(message) {
    // You can implement a loading spinner here
    console.log('Loading:', message);
}

function hideLoading() {
    // Hide loading spinner
    console.log('Loading complete');
}

function showAlert(type, message) {
    // Create and show Bootstrap alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert at the top of the page
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Reset import modal when it's closed
document.getElementById('importModal').addEventListener('hidden.bs.modal', function () {
    resetImport();
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
