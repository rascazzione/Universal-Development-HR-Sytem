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
                    'parent_id' => null, // Remove parent category functionality
                    'category_type' => sanitizeInput($_POST['category_type'])
                ];
                
                $competencyClass->createCategory($categoryData);
                setFlashMessage('Category created successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error creating category: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'update_category':
            // DEBUG: Log incoming POST data
            error_log("[DEBUG] update_category case - POST data: " . print_r($_POST, true));
            
            try {
                $categoryId = (int)$_POST['category_id'];
                error_log("[DEBUG] update_category - Category ID: " . $categoryId);
                
                $categoryData = [
                    'category_name' => sanitizeInput($_POST['category_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'parent_id' => null, // Remove parent category functionality
                    'category_type' => sanitizeInput($_POST['category_type'])
                ];
                
                error_log("[DEBUG] update_category - Sanitized data: " . print_r($categoryData, true));
                
                $result = $competencyClass->updateCategory($categoryId, $categoryData);
                error_log("[DEBUG] update_category - Update result: " . $result);
                
                setFlashMessage('Category updated successfully.', 'success');
                
            } catch (Exception $e) {
                error_log("[ERROR] update_category - Exception: " . $e->getMessage());
                setFlashMessage('Error updating category: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'create_competency':
            try {
                $competencyData = [
                    'competency_name' => sanitizeInput($_POST['competency_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'category_id' => (int)$_POST['category_id']
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
                    'category_id' => (int)$_POST['category_id']
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

// DEBUG: Log incoming GET parameters
error_log("[DEBUG] competencies.php - GET parameters: " . print_r($_GET, true));

// Get data for display
$selectedCategory = $_GET['category'] ?? null;
$selectedType = $_GET['type'] ?? null; // For competency filtering
$selectedCategoryType = $_GET['category_type'] ?? null; // For category filtering

// DEBUG: Log filter values
error_log("[DEBUG] competencies.php - Selected filters - Category: " . ($selectedCategory ?? 'null') .
          ", Type: " . ($selectedType ?? 'null') .
          ", CategoryType: " . ($selectedCategoryType ?? 'null'));

// Get categories filtered by type if specified
$categories = $competencyClass->getCategories(true, $selectedCategoryType);
// DEBUG: Log categories count
error_log("[DEBUG] competencies.php - Categories returned: " . count($categories));

// Get competencies filtered by both category and type
$competencies = $competencyClass->getCompetencies($selectedCategory, $selectedType);
// DEBUG: Log competencies count
error_log("[DEBUG] competencies.php - Competencies returned: " . count($competencies));

$categoryTypes = $competencyClass->getCategoryTypes();

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


<!-- Categories Overview -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <a data-bs-toggle="collapse" href="#categoriesCollapse" role="button" aria-expanded="false" aria-controls="categoriesCollapse" class="text-decoration-none">
                        <i class="fas fa-chevron-down me-2"></i>Categories Overview
                    </a>
                </h5>
            </div>
            <div class="collapse" id="categoriesCollapse">
                <div class="card-body">
                    <!-- Categories Filter -->
                    <form method="GET" class="row mb-3">
                        <div class="col-md-4">
                            <label for="category_type_filter" class="form-label">Filter Categories by Type</label>
                            <select class="form-select" id="category_type_filter" name="category_type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <?php foreach ($categoryTypes as $type => $label): ?>
                                <option value="<?php echo $type; ?>"
                                        <?php echo $selectedCategoryType === $type ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <a href="/admin/competencies.php" class="btn btn-outline-secondary">Clear All Filters</a>
                        </div>
                    </form>
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
                                        <br>
                                        <?php
                                        $typeColors = [
                                            'technical' => 'primary',
                                            'soft_skill' => 'success'
                                        ];
                                        $color = $typeColors[$category['category_type']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $categoryTypes[$category['category_type']] ?? $category['category_type']; ?></span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?category=<?php echo $category['id']; ?>">View Competencies</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editCategory(<?php echo $category['id']; ?>)">Edit Category</a></li>
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

<!-- Competencies Filters -->
<div class="row mb-4 mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Competencies Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="category" class="form-label">Filter Competencies by Category</label>
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
                        <label for="type" class="form-label">Filter Competencies by Type</label>
                        <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <?php foreach ($categoryTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"
                                    <?php echo $selectedType === $type ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="/admin/competencies.php" class="btn btn-outline-secondary">Clear Competency Filters</a>
                    </div>
                </form>
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
                                        'soft_skill' => 'success'
                                    ];
                                    $color = $typeColors[$competency['category_type']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $categoryTypes[$competency['category_type']] ?? $competency['category_type']; ?></span>
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
                                        <?php if ($competency['category_type'] === 'soft_skill'): ?>
                                        <button type="button" class="btn btn-outline-warning" onclick="viewSoftSkillLevels(<?php echo $competency['id']; ?>)">
                                            <i class="fas fa-layer-group"></i>
                                        </button>
                                        <?php endif; ?>
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
                        <label for="category_type" class="form-label">Category Type</label>
                        <select class="form-select" id="category_type" name="category_type" required>
                            <?php foreach ($categoryTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
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
                    
                    <div class="mb-3">
                        <label for="competency_name" class="form-label">Competency Name</label>
                        <input type="text" class="form-control" id="competency_name" name="competency_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="competency_category_type" class="form-label">Filter by Category Type</label>
                                <select class="form-select" id="competency_category_type" name="competency_category_type" onchange="filterCategoriesByType()">
                                    <option value="">All Types</option>
                                    <?php foreach ($categoryTypes as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category...</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            data-category-type="<?php echo $category['category_type']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                        <?php if ($category['parent_category_name']): ?>
                                            (<?php echo htmlspecialchars($category['parent_category_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
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
                    
                    <div class="mb-3">
                        <label for="edit_competency_name" class="form-label">Competency Name</label>
                        <input type="text" class="form-control" id="edit_competency_name" name="competency_name" required>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" id="category_id_edit">
                    
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_type" class="form-label">Category Type</label>
                        <select class="form-select" id="edit_category_type" name="category_type" required>
                            <?php foreach ($categoryTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_category_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
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

<!-- Soft Skill Levels Modal -->
<div class="modal fade" id="softSkillLevelsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Soft Skill Competency Levels</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="softSkillLevelsForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="competency_key" id="soft_skill_competency_key">
                    
                    <div class="mb-4">
                        <h6>Definition</h6>
                        <textarea class="form-control" id="soft_skill_definition" name="definition" rows="2" placeholder="Enter the definition of this competency..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Description</h6>
                        <textarea class="form-control" id="soft_skill_description" name="description" rows="4" placeholder="Enter a detailed description of this competency..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Level 1 - Basic</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="level_1_title" id="level_1_title" placeholder="Enter level 1 title...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Behaviors (Markdown compatible)</label>
                                        <small class="text-muted d-block mb-2">Use bullet points or numbered lists. Each line represents one behavior.</small>
                                        <textarea class="form-control" name="level_1_behaviors" id="level_1_behaviors" rows="6" placeholder="- Behavior 1&#10;- Behavior 2&#10;- Behavior 3&#10;- Behavior 4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Level 2 - Intermediate</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="level_2_title" id="level_2_title" placeholder="Enter level 2 title...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Behaviors (Markdown compatible)</label>
                                        <small class="text-muted d-block mb-2">Use bullet points or numbered lists. Each line represents one behavior.</small>
                                        <textarea class="form-control" name="level_2_behaviors" id="level_2_behaviors" rows="6" placeholder="- Behavior 1&#10;- Behavior 2&#10;- Behavior 3&#10;- Behavior 4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">Level 3 - Advanced</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="level_3_title" id="level_3_title" placeholder="Enter level 3 title...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Behaviors (Markdown compatible)</label>
                                        <small class="text-muted d-block mb-2">Use bullet points or numbered lists. Each line represents one behavior.</small>
                                        <textarea class="form-control" name="level_3_behaviors" id="level_3_behaviors" rows="6" placeholder="- Behavior 1&#10;- Behavior 2&#10;- Behavior 3&#10;- Behavior 4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">Level 4 - Expert</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="level_4_title" id="level_4_title" placeholder="Enter level 4 title...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Behaviors (Markdown compatible)</label>
                                        <small class="text-muted d-block mb-2">Use bullet points or numbered lists. Each line represents one behavior.</small>
                                        <textarea class="form-control" name="level_4_behaviors" id="level_4_behaviors" rows="6" placeholder="- Behavior 1&#10;- Behavior 2&#10;- Behavior 3&#10;- Behavior 4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSoftSkillLevels()">Save Levels</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Track collapse state persistence
document.addEventListener('DOMContentLoaded', function() {
    const categoriesCollapse = document.getElementById('categoriesCollapse');
    const categoryTypeFilter = document.getElementById('category_type_filter');
    const competencyCategoryFilter = document.getElementById('category');
    const competencyTypeFilter = document.getElementById('type');
    
    // Log initial state
    console.log('[DEBUG] Initial collapse state:', categoriesCollapse.classList.contains('show'));
    
    // Track collapse events
    categoriesCollapse.addEventListener('show.bs.collapse', function () {
        console.log('[DEBUG] Categories Overview is being shown');
        localStorage.setItem('categoriesCollapseState', 'shown');
    });
    
    categoriesCollapse.addEventListener('hide.bs.collapse', function () {
        console.log('[DEBUG] Categories Overview is being hidden');
        localStorage.setItem('categoriesCollapseState', 'hidden');
    });
    
    // Restore collapse state on page load
    const savedState = localStorage.getItem('categoriesCollapseState');
    console.log('[DEBUG] Saved collapse state:', savedState);
    
    if (savedState === 'shown') {
        // Show the collapse if it was previously shown
        // Use a timeout to ensure Bootstrap is fully loaded
        setTimeout(function() {
            const collapse = new bootstrap.Collapse(categoriesCollapse, {
                show: true
            });
        }, 100);
    }
    
    // Track filter changes
    if (categoryTypeFilter) {
        categoryTypeFilter.addEventListener('change', function() {
            console.log('[DEBUG] Category type filter changed to:', this.value);
            console.log('[DEBUG] Form will submit with URL:', this.form.action + '?' + new URLSearchParams(new FormData(this.form)).toString());
        });
    }
    
    if (competencyCategoryFilter) {
        competencyCategoryFilter.addEventListener('change', function() {
            console.log('[DEBUG] Competency category filter changed to:', this.value);
            console.log('[DEBUG] Form will submit with URL:', this.form.action + '?' + new URLSearchParams(new FormData(this.form)).toString());
        });
    }
    
    if (competencyTypeFilter) {
        competencyTypeFilter.addEventListener('change', function() {
            console.log('[DEBUG] Competency type filter changed to:', this.value);
            console.log('[DEBUG] Form will submit with URL:', this.form.action + '?' + new URLSearchParams(new FormData(this.form)).toString());
        });
    }
});

function editCompetency(competencyId) {
    // Fetch competency data and populate edit modal
    fetch(`/api/competency.php?id=${competencyId}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const competency = data.competency;
                document.getElementById('edit_competency_id').value = competency.id;
                document.getElementById('edit_competency_name').value = competency.competency_name;
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

function editCategory(categoryId) {
    console.log('[DEBUG] editCategory called with ID:', categoryId);
    
    // Fetch category data and populate edit modal
    fetch(`/api/category.php?id=${categoryId}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            console.log('[DEBUG] Category data received:', data);
            if (data.success) {
                const category = data.category;
                console.log('[DEBUG] Populating form with category:', category);
                
                document.getElementById('category_id_edit').value = category.id;
                document.getElementById('edit_category_name').value = category.category_name;
                document.getElementById('edit_category_type').value = category.category_type;
                document.getElementById('edit_category_description').value = category.description;
                
                console.log('[DEBUG] Form populated. Values:');
                console.log('  - ID:', document.getElementById('category_id_edit').value);
                console.log('  - Name:', document.getElementById('edit_category_name').value);
                console.log('  - Type:', document.getElementById('edit_category_type').value);
                console.log('  - Description:', document.getElementById('edit_category_description').value);
                
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching category data:', error);
            alert('Error loading category data');
        });
}

function filterCategoriesByType() {
    const selectedType = document.getElementById('competency_category_type').value;
    const categorySelect = document.getElementById('category_id');
    const options = categorySelect.querySelectorAll('option[data-category-type]');
    
    // Show all categories if no type selected
    if (!selectedType) {
        options.forEach(option => {
            option.style.display = '';
        });
        return;
    }
    
    // Filter categories by selected type
    options.forEach(option => {
        const categoryType = option.getAttribute('data-category-type');
        if (categoryType === selectedType) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Reset selection if current selection is hidden
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    if (selectedOption && selectedOption.style.display === 'none') {
        categorySelect.value = '';
    }
}

function viewCompetencyUsage(competencyId) {
    // Load competency usage data
    fetch(`/api/competency_usage.php?id=${competencyId}`, {
        credentials: 'include'
    })
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

function viewSoftSkillLevels(competencyId) {
    // Load soft skill level data
    fetch(`/api/soft_skill_levels.php?competency_id=${competencyId}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const levels = data.levels;
                const competencyKey = data.competency_key;
                
                // Set competency key
                document.getElementById('soft_skill_competency_key').value = competencyKey;
                
                // Set definition and description
                document.getElementById('soft_skill_definition').value = levels.definition || '';
                document.getElementById('soft_skill_description').value = levels.description || '';
                
                // Set level titles and behaviors (convert array to markdown)
                for (let i = 1; i <= 4; i++) {
                    const level = levels.levels[i.toString()];
                    if (level) {
                        document.getElementById(`level_${i}_title`).value = level.title || '';
                        
                        // Convert behaviors array to markdown list
                        const behaviorsText = level.behaviors ? level.behaviors.map(b => `- ${b}`).join('\n') : '';
                        document.getElementById(`level_${i}_behaviors`).value = behaviorsText;
                    }
                }
                
                new bootstrap.Modal(document.getElementById('softSkillLevelsModal')).show();
            } else {
                alert('Error loading soft skill levels: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching soft skill levels:', error);
            alert('Error loading soft skill levels');
        });
}

function saveSoftSkillLevels() {
    // Collect form data
    const formData = new FormData(document.getElementById('softSkillLevelsForm'));
    
    // Build levels structure
    const levels = {
        name: document.getElementById('soft_skill_definition').value ?
              document.getElementById('soft_skill_definition').value.split(' ').slice(0, 2).join(' ') : '',
        definition: document.getElementById('soft_skill_definition').value,
        description: document.getElementById('soft_skill_description').value,
        levels: {}
    };
    
    // Collect level data
    for (let i = 1; i <= 4; i++) {
        // Parse markdown text to extract behaviors
        const behaviorsText = document.getElementById(`level_${i}_behaviors`).value;
        const behaviors = parseMarkdownBehaviors(behaviorsText);
        
        levels.levels[i.toString()] = {
            title: document.getElementById(`level_${i}_title`).value,
            behaviors: behaviors
        };
    }
    
    // Prepare data for API
    const postData = new URLSearchParams();
    postData.append('csrf_token', formData.get('csrf_token'));
    postData.append('competency_key', formData.get('competency_key'));
    postData.append('levels', JSON.stringify(levels));
    
    // Send to API
    fetch('/api/soft_skill_levels.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: postData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Soft skill levels saved successfully!');
            bootstrap.Modal.getInstance(document.getElementById('softSkillLevelsModal')).hide();
        } else {
            alert('Error saving soft skill levels: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error saving soft skill levels:', error);
        alert('Error saving soft skill levels');
    });
}

function parseMarkdownBehaviors(text) {
    // Split by lines and filter out empty lines
    const lines = text.split('\n').filter(line => line.trim());
    
    const behaviors = [];
    
    for (const line of lines) {
        // Remove markdown list markers (-, *, 1., 2., etc.)
        let cleanLine = line.trim();
        
        // Remove bullet points
        cleanLine = cleanLine.replace(/^[-*+]\s*/, '');
        
        // Remove numbered lists
        cleanLine = cleanLine.replace(/^\d+\.\s*/, '');
        
        // Add the behavior if it's not empty
        if (cleanLine) {
            behaviors.push(cleanLine);
        }
    }
    
    // Ensure we have at least 4 behaviors, fill with empty strings if needed
    while (behaviors.length < 4) {
        behaviors.push('');
    }
    
    // Return only first 4 behaviors
    return behaviors.slice(0, 4);
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>