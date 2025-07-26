<?php
/**
 * Departments Management
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Department.php';

// Require HR admin authentication
requireAuth();
if (!hasPermission('*')) {
    setFlashMessage('You do not have permission to access this page.', 'error');
    redirect('/dashboard.php');
}

// Initialize department class
$departmentClass = new Department();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/departments.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_department':
            try {
                $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
                $departmentData = [
                    'department_name' => sanitizeInput($_POST['department_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'manager_id' => $managerId,
                    'created_by' => $_SESSION['user_id']
                ];
                
                $departmentClass->createDepartment($departmentData);
                setFlashMessage('Department created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating department: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'update_department':
            try {
                $departmentId = (int)$_POST['department_id'];
                $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
                $departmentData = [
                    'department_name' => sanitizeInput($_POST['department_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'manager_id' => $managerId
                ];
                
                $departmentClass->updateDepartment($departmentId, $departmentData);
                setFlashMessage('Department updated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error updating department: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_department':
            try {
                $departmentId = (int)$_POST['department_id'];
                $departmentClass->deleteDepartment($departmentId);
                setFlashMessage('Department deactivated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deactivating department: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'restore_department':
            try {
                $departmentId = (int)$_POST['department_id'];
                $departmentClass->restoreDepartment($departmentId);
                setFlashMessage('Department reactivated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error reactivating department: ' . $e->getMessage(), 'error');
            }
            break;
    }
    
    redirect('/admin/departments.php');
}

// Get departments for display
$departments = $departmentClass->getAllDepartments();

// Get available managers for dropdowns
$availableManagers = $departmentClass->getAvailableManagers();

$pageTitle = 'Departments Management';
$pageHeader = true;
$pageDescription = 'Manage company departments';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Departments Management</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                <i class="fas fa-plus me-2"></i>Create New Department
            </button>
        </div>
    </div>
</div>

<!-- Information Alert -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Department Management:</strong> When you deactivate a department, it's preserved in the system but hidden from dropdowns. 
            You can reactivate it anytime to make it available again. This prevents data loss while maintaining clean department lists.
        </div>
    </div>
</div>

<!-- Departments List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Company Departments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($departments)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No departments found</h5>
                    <p class="text-muted">Create your first department to get started.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Description</th>
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $department): ?>
                            <tr class="<?php echo !$department['is_active'] ? 'table-secondary text-muted' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($department['department_name']); ?></strong>
                                    <?php if (!$department['is_active']): ?>
                                        <small class="text-muted d-block">(Inactive)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($department['description']): ?>
                                        <?php echo htmlspecialchars(substr($department['description'], 0, 100)); ?>
                                        <?php if (strlen($department['description']) > 100): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No description</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($department['manager_name']): ?>
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?php echo htmlspecialchars($department['manager_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No manager assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($department['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($department['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($department['is_active']): ?>
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editDepartment(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['department_name']); ?>', '<?php echo htmlspecialchars($department['description']); ?>', <?php echo $department['manager_id'] ?? 'null'; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteDepartment(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['department_name']); ?>')">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="restoreDepartment(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['department_name']); ?>')">
                                            <i class="fas fa-undo"></i> Reactivate
                                        </button>
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

<!-- Create Department Modal -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_department">
                    
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="manager_id" class="form-label">Department Manager</label>
                        <select class="form-select" id="manager_id" name="manager_id">
                            <option value="">No manager assigned</option>
                            <?php foreach ($availableManagers as $manager): ?>
                            <option value="<?php echo $manager['employee_id']; ?>">
                                <?php echo htmlspecialchars($manager['full_name']); ?>
                                <?php if ($manager['position']): ?>
                                    - <?php echo htmlspecialchars($manager['position']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_department">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    
                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="edit_department_name" name="department_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_manager_id" class="form-label">Department Manager</label>
                        <select class="form-select" id="edit_manager_id" name="manager_id">
                            <option value="">No manager assigned</option>
                            <?php foreach ($availableManagers as $manager): ?>
                            <option value="<?php echo $manager['employee_id']; ?>">
                                <?php echo htmlspecialchars($manager['full_name']); ?>
                                <?php if ($manager['position']): ?>
                                    - <?php echo htmlspecialchars($manager['position']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDepartment(id, name, description, managerId) {
    document.getElementById('edit_department_id').value = id;
    document.getElementById('edit_department_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_manager_id').value = managerId || '';
    var editModal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
    editModal.show();
}

function deleteDepartment(id, name) {
    if (confirm('Are you sure you want to deactivate the department "' + name + '"?\n\nThis will mark it as inactive but preserve all data. You can reactivate it later if needed.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_department">
            <input type="hidden" name="department_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restoreDepartment(id, name) {
    if (confirm('Are you sure you want to reactivate the department "' + name + '"?\n\nThis will make it available for use again.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="restore_department">
            <input type="hidden" name="department_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
