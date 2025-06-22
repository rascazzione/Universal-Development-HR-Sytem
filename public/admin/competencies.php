<?php
/**
 * Competencies Management
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Competency.php';

// Require HR admin authentication
requireAuth();
if (!hasPermission('*')) {
    setFlashMessage('You do not have permission to access this page.', 'error');
    redirect('/dashboard.php');
}

// Initialize classes
$competencyClass = new Competency();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/competencies.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_category':
            try {
                $categoryData = [
                    'category_name' => sanitizeInput($_POST['category_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
                ];
                
                $competencyClass->createCategory($categoryData);
                setFlashMessage('Category created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating category: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'create_competency':
            try {
                $competencyData = [
                    'competency_name' => sanitizeInput($_POST['competency_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'category_id' => (int)$_POST['category_id'],
                    'competency_type' => sanitizeInput($_POST['competency_type'])
                ];
                
                $competencyClass->createCompetency($competencyData);
                setFlashMessage('Competency created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating competency: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'update_competency':
            try {
                $competencyId = (int)$_POST['competency_id'];
                $competencyData = [
                    'competency_name' => sanitizeInput($_POST['competency_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'category_id' => (int)$_POST['category_id'],
                    'competency_type' => sanitizeInput($_POST['competency_type'])
                ];
                
                $competencyClass->updateCompetency($competencyId, $competencyData);
                setFlashMessage('Competency updated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error updating competency: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_competency':
            try {
                $competencyId = (int)$_POST['competency_id'];
                $competencyClass->deleteCompetency($competencyId);
                setFlashMessage('Competency deleted successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting competency: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_category':
            try {
                $categoryId = (int)$_POST['category_id'];
                $competencyClass->deleteCategory($categoryId);
                setFlashMessage('Category deleted successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting category: ' . $e->getMessage(), 'error');
            }
            break;
    }
}

// Get data for display
$selectedCategory = $_GET['category'] ?? null;
$selectedType = $_GET['type'] ?? null;
$categories = $competencyClass->getCategories();
$competencies = $competencyClass->getCompetencies($selectedCategory, $selectedType);
$competencyTypes = $competencyClass->getCompetencyTypes();

$pageTitle = 'Competencies Management';
$pageHeader = true;
$pageDescription = 'Manage skills, knowledge, and competencies catalog';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Competencies Management</h4>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="fas fa-folder-plus me-2"></i>New Category
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCompetencyModal">
                    <i class="fas fa-plus me-2"></i>New Competency
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
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $selectedCategory == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                                <?php if ($category['parent_category_name']): ?>
                                    (<?php echo htmlspecialchars($category['parent_category_name']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Filter by Type</label>
                        <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <?php foreach ($competencyTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>" 
                                    <?php echo $selectedType === $type ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="/admin/competencies.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Categories Overview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Categories Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h6>
                                        <?php if ($category['parent_category_name']): ?>
                                        <small class="text-muted">Under: <?php echo htmlspecialchars($category['parent_category_name']); ?></small><br>
                                        <?php endif; ?>
                                        <small class="text-muted"><?php echo $category['competency_count']; ?> competencies</small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?category=<?php echo $category['id']; ?>">View Competencies</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategory(<?php echo $category['id']; ?>)">Delete Category</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php if ($category['description']): ?>
                                <p class="card-text small"><?php echo htmlspecialchars($category['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Competencies List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    Competencies
                    <?php if ($selectedCategory || $selectedType): ?>
                    <span class="badge bg-primary ms-2">Filtered</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($competencies)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No competencies found</h5>
                    <p class="text-muted">Create your first competency to get started.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Competency Name</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competencies as $competency): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($competency['competency_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($competency['category_name']); ?></span>
                                    <?php if ($competency['parent_category_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($competency['parent_category_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeColors = [
                                        'technical' => 'primary',
                                        'soft_skill' => 'success',
                                        'leadership' => 'warning',
                                        'core' => 'danger'
                                    ];
                                    $color = $typeColors[$competency['competency_type']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $competencyTypes[$competency['competency_type']] ?? $competency['competency_type']; ?></span>
                                </td>
                                <td>
                                    <?php if ($competency['description']): ?>
                                    <small><?php echo htmlspecialchars(substr($competency['description'], 0, 100)); ?>...</small>
                                    <?php else: ?>
                                    <small class="text-muted">No description</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="editCompetency(<?php echo $competency['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="viewCompetencyUsage(<?php echo $competency['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteCompetency(<?php echo $competency['id']; ?>)">
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

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_category">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category (Optional)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categories as $category): ?>
                            <?php if (!$category['parent_id']): // Only show top-level categories ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Competency Modal -->
<div class="modal fade" id="createCompetencyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Competency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_competency">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="competency_name" class="form-label">Competency Name</label>
                                <input type="text" class="form-control" id="competency_name" name="competency_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="competency_type" class="form-label">Type</label>
                                <select class="form-select" id="competency_type" name="competency_type" required>
                                    <?php foreach ($competencyTypes as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                                <?php if ($category['parent_category_name']): ?>
                                    (<?php echo htmlspecialchars($category['parent_category_name']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="competency_description" class="form-label">Description</label>
                        <textarea class="form-control" id="competency_description" name="description" rows="4" 
                                  placeholder="Describe what this competency involves and how it can be demonstrated..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Competency</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Competency Modal -->
<div class="modal fade" id="editCompetencyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Competency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_competency">
                    <input type="hidden" name="competency_id" id="edit_competency_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_competency_name" class="form-label">Competency Name</label>
                                <input type="text" class="form-control" id="edit_competency_name" name="competency_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_competency_type" class="form-label">Type</label>
                                <select class="form-select" id="edit_competency_type" name="competency_type" required>
                                    <?php foreach ($competencyTypes as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select class="form-select" id="edit_category_id" name="category_id" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                                <?php if ($category['parent_category_name']): ?>
                                    (<?php echo htmlspecialchars($category['parent_category_name']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_competency_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_competency_description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Competency</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Competency Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Competency Usage</h5>
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
function editCompetency(competencyId) {
    // Fetch competency data and populate edit modal
    fetch(`/api/competency.php?id=${competencyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const competency = data.competency;
                document.getElementById('edit_competency_id').value = competency.id;
                document.getElementById('edit_competency_name').value = competency.competency_name;
                document.getElementById('edit_competency_type').value = competency.competency_type;
                document.getElementById('edit_category_id').value = competency.category_id;
                document.getElementById('edit_competency_description').value = competency.description;
                
                new bootstrap.Modal(document.getElementById('editCompetencyModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching competency data:', error);
            alert('Error loading competency data');
        });
}

function deleteCompetency(competencyId) {
    if (confirm('Are you sure you want to delete this competency? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_competency">
            <input type="hidden" name="competency_id" value="${competencyId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This will also delete all competencies in this category.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="${categoryId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewCompetencyUsage(competencyId) {
    // Load competency usage data
    fetch(`/api/competency_usage.php?id=${competencyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('usageContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('usageModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching competency usage:', error);
            alert('Error loading competency usage data');
        });
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>