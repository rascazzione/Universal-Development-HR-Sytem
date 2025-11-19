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
    // DEBUG: Log form submission attempt
    $submissionId = uniqid('form_sub_', true);
    error_log("[DEBUG] Form submission detected - ID: {$submissionId} - Action: " . ($_POST['action'] ?? 'unknown') . " - Timestamp: " . date('Y-m-d H:i:s'));
    error_log("[DEBUG] Form submission details - POST data: " . print_r($_POST, true));
    error_log("[DEBUG] Form submission details - Server REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    error_log("[DEBUG] Form submission details - HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'not set'));
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        error_log("[DEBUG] CSRF token validation failed for submission ID: {$submissionId}");
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/competencies.php');
    }
    
    $action = $_POST['action'] ?? '';
    error_log("[DEBUG] Processing action: {$action} for submission ID: {$submissionId}");
    
    $redirectUrl = '/admin/competencies.php';
    $preserveFilters = false;
    
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
                error_log("[DEBUG] Category created successfully for submission ID: {$submissionId}");
                
            } catch (Exception $e) {
                setFlashMessage('Error creating category: ' . $e->getMessage(), 'error');
                error_log("[ERROR] Category creation failed for submission ID: {$submissionId} - " . $e->getMessage());
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
                error_log("[DEBUG] Category updated successfully for submission ID: {$submissionId}");
                
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
                error_log("[DEBUG] Competency created successfully for submission ID: {$submissionId}");
                
            } catch (Exception $e) {
                setFlashMessage('Error creating competency: ' . $e->getMessage(), 'error');
                error_log("[ERROR] Competency creation failed for submission ID: {$submissionId} - " . $e->getMessage());
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
                error_log("[DEBUG] Competency updated successfully for submission ID: {$submissionId}");
                
            } catch (Exception $e) {
                setFlashMessage('Error updating competency: ' . $e->getMessage(), 'error');
                error_log("[ERROR] Competency update failed for submission ID: {$submissionId} - " . $e->getMessage());
            }
            break;
            
        case 'delete_competency':
            try {
                $competencyId = (int)$_POST['competency_id'];
                $competencyClass->deleteCompetency($competencyId);
                setFlashMessage('Competency deleted successfully.', 'success');
                error_log("[DEBUG] Competency deleted successfully for submission ID: {$submissionId}");
                $preserveFilters = true; // Preserve filters when deleting to maintain context
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting competency: ' . $e->getMessage(), 'error');
                error_log("[ERROR] Competency deletion failed for submission ID: {$submissionId} - " . $e->getMessage());
            }
            break;
            
        case 'delete_category':
            try {
                $categoryId = (int)$_POST['category_id'];
                $competencyClass->deleteCategory($categoryId);
                setFlashMessage('Category deleted successfully.', 'success');
                error_log("[DEBUG] Category deleted successfully for submission ID: {$submissionId}");
                $preserveFilters = true; // Preserve filters when deleting to maintain context
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting category: ' . $e->getMessage(), 'error');
                error_log("[ERROR] Category deletion failed for submission ID: {$submissionId} - " . $e->getMessage());
            }
            break;
    }
    
    // Build redirect URL with preserved filters if needed
    if ($preserveFilters) {
        $queryParams = [];
        if (!empty($_GET['category'])) {
            $queryParams['category'] = $_GET['category'];
        }
        if (!empty($_GET['type'])) {
            $queryParams['type'] = $_GET['type'];
        }
        if (!empty($_GET['category_type'])) {
            $queryParams['category_type'] = $_GET['category_type'];
        }
        
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
    }
    
    error_log("[DEBUG] Redirecting to: {$redirectUrl} for submission ID: {$submissionId}");
    redirect($redirectUrl);
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
$softSkillStatus = $competencyClass->getSoftSkillDefinitionStatus();
$softSkillFileLookup = [];
foreach ($softSkillStatus['files'] as $file) {
    $normalizedKey = $competencyClass->normalizeCompetencyKeyValue($file['competency_key'] ?? '');
    if ($normalizedKey) {
        $softSkillFileLookup[$normalizedKey] = $file;
    }
}
$softSkillMissingLookup = [];
foreach ($softSkillStatus['missing_definitions'] as $missing) {
    $normalizedKey = $competencyClass->normalizeCompetencyKeyValue($missing['competency_key'] ?? '');
    if ($normalizedKey) {
        $softSkillMissingLookup[$normalizedKey] = true;
    }
}
$softSkillCategories = array_values(array_filter($categories, function ($category) {
    $categoryType = $category['category_type'] ?? '';
    $moduleType = $category['module_type'] ?? '';
    return $categoryType === 'soft_skill' || $moduleType === 'soft_skill';
}));
$canImportSoftSkills = !empty($softSkillCategories);

$pageTitle = 'Competencies Management';
$pageHeader = true;
$pageDescription = 'Manage skills, knowledge, and competencies catalog';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4>Competencies Management</h4>
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-export me-2"></i>Download Catalog
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/api/competency_catalog.php?source=db&format=csv&download=1" target="_blank" rel="noopener">
                                <i class="fas fa-database me-2 text-primary"></i>Current Catalog (CSV)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/api/competency_catalog.php?source=db&format=json" target="_blank" rel="noopener">
                                <i class="fas fa-code me-2 text-muted"></i>Current Catalog (JSON)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/api/competency_catalog.php?source=starter&format=csv&download=1" target="_blank" rel="noopener">
                                <i class="fas fa-lightbulb me-2 text-success"></i>Starter Library (CSV)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/api/competency_catalog.php?source=starter&format=json" target="_blank" rel="noopener">
                                <i class="fas fa-code-branch me-2 text-warning"></i>Starter Library (JSON)
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCompetencyModal">
                    <i class="fas fa-upload me-2"></i>Import Competencies
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
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
                                <?php
                                $competencyKeyNormalized = $competencyClass->normalizeCompetencyKeyValue($competency['competency_key'] ?? '');
                                $hasSoftSkillJson = $competencyKeyNormalized && isset($softSkillFileLookup[$competencyKeyNormalized]);
                                $missingSoftSkillJson = $competency['category_type'] === 'soft_skill' && !$hasSoftSkillJson;
                                ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($competency['competency_name']); ?></strong>
                                    <?php if (!empty($competency['competency_key'])): ?>
                                    <br><small class="text-muted">Key: <?php echo htmlspecialchars($competency['competency_key']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($missingSoftSkillJson): ?>
                                    <span class="badge bg-danger ms-1">Missing JSON</span>
                                    <?php elseif ($hasSoftSkillJson): ?>
                                    <span class="badge bg-success ms-1">JSON Linked</span>
                                    <?php endif; ?>
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

<!-- Soft Skill JSON Catalog -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-code me-2 text-primary"></i>Soft Skill JSON Catalog Health
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="loadSoftSkillCatalogStatus(true)">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="viewSoftSkillLevels(null)">
                        <i class="fas fa-plus me-1"></i>New JSON Definition
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="softSkillCatalogStatus" class="alert alert-info mb-3" role="alert">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Loading catalog status...
                </div>
                <div id="softSkillCatalogTables" class="d-none">
                    <div id="softSkillFilesTable" class="table-responsive mb-4"></div>
                    <div id="softSkillInconsistencies" class="d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Soft Skill JSON Modal -->
<div class="modal fade" id="importSoftSkillJsonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Soft Skills from JSON Catalog</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importSoftSkillJsonForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="operation" value="import_catalog">
                    <input type="hidden" name="competency_key" id="importSoftSkillJsonKey">
                    <div class="alert alert-light border small mb-3">
                        <div class="fw-semibold mb-1">Use your versioned JSON files as the source of truth.</div>
                        <div>The action below will import the selected orphaned JSON file into the chosen category.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Selected JSON File</label>
                        <div id="softSkillImportSelection" class="alert alert-warning small mb-0">
                            Select an orphaned JSON entry from the catalog list to import it.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="importSoftSkillCategory" class="form-label">Target Soft Skill Category</label>
                        <select class="form-select" id="importSoftSkillCategory" name="category_id" <?php echo $canImportSoftSkills ? '' : 'disabled'; ?> required>
                            <?php if ($canImportSoftSkills): ?>
                                <?php foreach ($softSkillCategories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No soft skill categories available</option>
                            <?php endif; ?>
                        </select>
                        <?php if ($canImportSoftSkills): ?>
                        <div class="form-text">Imported competencies can be edited or re-assigned later.</div>
                        <?php else: ?>
                        <div class="form-text text-danger">Create at least one category with type "Soft Skills" and reload this page to enable imports.</div>
                        <?php endif; ?>
                    </div>
                    <div id="softSkillImportFeedback" class="alert d-none mb-0 small" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" <?php echo $canImportSoftSkills ? '' : 'disabled'; ?>>
                        <i class="fas fa-database me-1"></i>Import Soft Skills
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Competencies Modal -->
<div class="modal fade" id="importCompetencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Competencies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                        <div>
                            <div class="fw-semibold">Need a template?</div>
                            <div class="small text-muted">Download the current catalog or the curated starter library to tweak offline.</div>
                            <div class="mt-2 small">
                                <a href="/api/competency_catalog.php?source=starter&format=csv&download=1" target="_blank" rel="noopener" class="me-2">
                                    <i class="fas fa-file-csv me-1"></i>Starter library (CSV)
                                </a>
                                <a href="/api/competency_catalog.php?source=starter&format=json" target="_blank" rel="noopener" class="me-2">
                                    <i class="fas fa-code me-1"></i>Starter library (JSON)
                                </a>
                                <a href="/api/competency_catalog.php?source=db&format=csv&download=1" target="_blank" rel="noopener">
                                    <i class="fas fa-database me-1"></i>Current catalog (CSV)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mb-2">Upload a CSV file with the following headers (case-insensitive):</p>
                <ul class="small mb-3">
                    <li><strong>Competency Name</strong> (required)</li>
                    <li><strong>Description</strong> (optional)</li>
                    <li><strong>Category</strong> (required &ndash; created automatically if missing)</li>
                    <li><strong>Category Type</strong> (technical or soft_skill)</li>
                    <li><strong>Module Type</strong> (optional &ndash; defaults to category type)</li>
                    <li><strong>Symbol</strong>, <strong>Max Level</strong>, <strong>Level Type</strong> (optional visual settings)</li>
                    <li><strong>Competency Key</strong> (optional; auto-generated for soft skills)</li>
                </ul>
                <p class="text-muted small mb-3">Duplicate competency name + category pairs will be updated. Extra columns are ignored.</p>
                <form id="competencyImportForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="competencyCsvFile" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="competencyCsvFile" accept=".csv" required>
                    </div>
                </form>
                <div id="competencyImportFeedback" class="alert alert-info d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="competencyImportButton" onclick="importCompetencies()">
                    Import
                </button>
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
            <form method="POST" onsubmit="return handleFormSubmit(this, 'Updating competency...')">
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
            <form id="editCategoryForm" method="POST" onsubmit="return handleFormSubmit(this, 'Updating category...')">
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

<!-- Soft Skill JSON Editor Modal -->
<div class="modal fade" id="softSkillLevelsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Soft Skill JSON Editor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="softSkillJsonForm" onsubmit="event.preventDefault(); saveSoftSkillLevels();">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="competency_key" id="soft_skill_competency_key">
                    
                    <div class="alert alert-light border small mb-3">
                        <div class="fw-semibold mb-1">One JSON file per soft skill competency</div>
                        <div>Copy/paste your entire definition (name, definition, description, and the four levels) into the editor below. The file will be stored in <code><?php echo htmlspecialchars(rtrim($competencyClass->getSoftSkillDefinitionsRoot(), '/')); ?>/&lt;competency_key&gt;.json</code> so it can be versioned alongside the app.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="soft_skill_json" class="form-label">JSON Definition</label>
                        <textarea class="form-control font-monospace" id="soft_skill_json" rows="22" spellcheck="false" style="min-height: 400px; resize: vertical;" placeholder='{
    "name": "",
    "definition": "",
    "description": "",
    "levels": {
        "1": {"title": "", "behaviors": ["", "", "", ""]},
        "2": {"title": "", "behaviors": ["", "", "", ""]},
        "3": {"title": "", "behaviors": ["", "", "", ""]},
        "4": {"title": "", "behaviors": ["", "", "", ""]}
    }
}'></textarea>
                        <div class="form-text">All four levels must include exactly four behaviors. Additional metadata is preserved.</div>
                    </div>
                    
                    <div id="softSkillJsonMeta" class="alert alert-secondary small d-flex flex-column gap-1"></div>
                    <div id="softSkillJsonFeedback" class="alert d-none mt-3" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save JSON</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const softSkillImportEnabled = <?php echo $canImportSoftSkills ? 'true' : 'false'; ?>;

// Form submission protection function
function handleFormSubmit(form, loadingText = 'Processing...') {
    console.log('[DEBUG] handleFormSubmit called for form:', form, 'Timestamp:', new Date().toISOString());
    
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) {
        console.log('[DEBUG] No submit button found, allowing submission');
        return true;
    }
    
    if (submitButton.disabled) {
        console.log('[DEBUG] Submit button already disabled, preventing double submission');
        return false;
    }
    
    // Store original button content
    const originalContent = submitButton.innerHTML;
    
    // Disable button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
    
    // Log submission attempt
    console.log('[DEBUG] Form submission protection activated, button disabled');
    
    // Allow form to submit normally (will be redirected by server-side PRG)
    return true;
}

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
    
    const softSkillImportForm = document.getElementById('importSoftSkillJsonForm');
    if (softSkillImportForm) {
        softSkillImportForm.addEventListener('submit', handleSoftSkillJsonImport);
        setSoftSkillImportSelection('');
    }

    loadSoftSkillCatalogStatus();
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
    console.log('[DEBUG] deleteCompetency called with ID:', competencyId, 'Timestamp:', new Date().toISOString());
    if (confirm('Are you sure you want to delete this competency? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_competency">
            <input type="hidden" name="competency_id" value="${competencyId}">
        `;
        document.body.appendChild(form);
        console.log('[DEBUG] Submitting delete form for competency ID:', competencyId);
        form.submit();
    }
}

function deleteCategory(categoryId) {
    console.log('[DEBUG] deleteCategory called with ID:', categoryId, 'Timestamp:', new Date().toISOString());
    if (confirm('Are you sure you want to delete this category? This will also delete all competencies in this category.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="${categoryId}">
        `;
        document.body.appendChild(form);
        console.log('[DEBUG] Submitting delete form for category ID:', categoryId);
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

function viewSoftSkillLevels(competencyId = null, directKey = null) {
    let queryString = '';
    if (competencyId) {
        queryString = `competency_id=${competencyId}`;
    } else if (directKey) {
        queryString = `competency_key=${encodeURIComponent(directKey)}`;
    } else {
        const manualKey = prompt('Enter the soft skill competency key (e.g., communication_basics)');
        if (!manualKey) {
            return;
        }
        queryString = `competency_key=${encodeURIComponent(manualKey)}`;
    }

    setSoftSkillJsonFeedback();

    fetch(`/api/soft_skill_levels.php?${queryString}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Unable to load soft skill levels');
            }

            const keyInput = document.getElementById('soft_skill_competency_key');
            const jsonTextarea = document.getElementById('soft_skill_json');

            if (!keyInput || !jsonTextarea) {
                throw new Error('Modal elements not found');
            }

            keyInput.value = data.competency_key;
            jsonTextarea.value = data.raw_json || JSON.stringify(data.levels, null, 2);

            updateSoftSkillJsonMeta({
                competency_key: data.competency_key,
                file_path: data.file_path,
                file_exists: data.file_exists,
                last_modified: data.last_modified
            });

            const modal = new bootstrap.Modal(document.getElementById('softSkillLevelsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching soft skill levels:', error);
            alert(error.message || 'Error loading soft skill levels');
        });
}

function saveSoftSkillLevels() {
    console.log('[DEBUG] saveSoftSkillLevels called at:', new Date().toISOString());
    const form = document.getElementById('softSkillJsonForm');
    const keyInput = document.getElementById('soft_skill_competency_key');
    const jsonTextarea = document.getElementById('soft_skill_json');
    const saveButton = form.querySelector('button[type="submit"]');

    if (saveButton.disabled) {
        console.log('[DEBUG] saveSoftSkillLevels - Button already disabled, ignoring double click');
        return;
    }

    if (!keyInput.value.trim()) {
        setSoftSkillJsonFeedback('Competency key is required.', 'warning');
        return;
    }

    if (!jsonTextarea.value.trim()) {
        setSoftSkillJsonFeedback('JSON payload cannot be empty.', 'warning');
        return;
    }

    try {
        // Try to parse the JSON to validate it
        JSON.parse(jsonTextarea.value.trim());
    } catch (error) {
        setSoftSkillJsonFeedback(`Invalid JSON: ${error.message}`, 'danger');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
    formData.append('competency_key', keyInput.value.trim());
    formData.append('raw_json', jsonTextarea.value);

    const originalLabel = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving';
    console.log('[DEBUG] saveSoftSkillLevels - Button disabled, submitting AJAX request');

    fetch('/api/soft_skill_levels.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
    })
        .then(response => {
            // Check if response is JSON before parsing
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Not JSON - probably an error page
                return response.text().then(text => {
                    console.error('Non-JSON response received:', text);
                    throw new Error('Server returned an error. Check the browser console for details.');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Save response received:', data);
            if (!data.success) {
                console.error('[ERROR] Save failed:', data.error);
                throw new Error(data.error || 'Unable to save definition');
            }
            console.log('[DEBUG] Save successful, updating UI...');
            setSoftSkillJsonFeedback('Soft skill JSON saved successfully.', 'success');
            if (data.file_path) {
                updateSoftSkillJsonMeta({
                    competency_key: keyInput.value.trim(),
                    file_path: data.file_path,
                    file_exists: true,
                    last_modified: new Date().toISOString()
                });
            }
            loadSoftSkillCatalogStatus(true);
            
            // Close modal after successful save to prevent UI freezing
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('softSkillLevelsModal'));
                if (modal) {
                    modal.hide();
                }
                // Clear modal backdrop and reset state
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }, 1000);
        })
        .catch(error => {
            console.error('Error saving soft skill levels:', error);
            setSoftSkillJsonFeedback(error.message || 'Error saving soft skill JSON.', 'danger');
        })
        .finally(() => {
            saveButton.disabled = false;
            saveButton.innerHTML = originalLabel;
        });
}

function updateSoftSkillJsonMeta(info = {}) {
    const meta = document.getElementById('softSkillJsonMeta');
    if (!meta) return;
    
    const filePath = info.file_path ? `<code>${escapeHtml(info.file_path)}</code>` : '<em>File will be created on save</em>';
    const statusBadge = info.file_exists
        ? '<span class="badge bg-success ms-2">On disk</span>'
        : '<span class="badge bg-warning text-dark ms-2">Missing</span>';
    const lastUpdated = info.last_modified ? new Date(info.last_modified).toLocaleString() : 'Never';
    
    meta.innerHTML = `
        <div><strong>Key:</strong> ${escapeHtml(info.competency_key || 'n/a')}</div>
        <div><strong>File:</strong> ${filePath} ${statusBadge}</div>
        <div><strong>Last updated:</strong> ${escapeHtml(lastUpdated)}</div>
        <div class="text-muted">Keep this JSON under version control to audit every change.</div>
    `;
}

function setSoftSkillJsonFeedback(message = '', variant = null) {
    const feedback = document.getElementById('softSkillJsonFeedback');
    if (!feedback) return;
    
    if (!message || !variant) {
        feedback.classList.add('d-none');
        feedback.textContent = '';
        return;
    }
    
    feedback.className = `alert alert-${variant} mt-3`;
    feedback.textContent = message;
}

function loadSoftSkillCatalogStatus(force = false) {
    const statusEl = document.getElementById('softSkillCatalogStatus');
    const tablesWrapper = document.getElementById('softSkillCatalogTables');
    if (!statusEl) return;
    
    statusEl.className = 'alert alert-info mb-3';
    statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Refreshing catalog...';
    
    console.log('[DEBUG] loadSoftSkillCatalogStatus - Making request to /api/soft_skill_levels.php?overview=1');
    
    fetch('/api/soft_skill_levels.php?overview=1', { credentials: 'include' })
        .then(response => {
            console.log('[DEBUG] loadSoftSkillCatalogStatus - Response status:', response.status);
            console.log('[DEBUG] loadSoftSkillCatalogStatus - Response headers:', response.headers);
            console.log('[DEBUG] loadSoftSkillCatalogStatus - Content-Type:', response.headers.get('content-type'));
            
            // Clone the response to read it twice
            const responseClone = response.clone();
            
            // First, get the raw text to see what we're actually receiving
            return response.text().then(text => {
                console.log('[DEBUG] loadSoftSkillCatalogStatus - Raw response text:', text);
                console.log('[DEBUG] loadSoftSkillCatalogStatus - Response text length:', text.length);
                console.log('[DEBUG] loadSoftSkillCatalogStatus - First 200 chars:', text.substring(0, 200));
                
                // Try to parse JSON
                try {
                    const data = JSON.parse(text);
                    console.log('[DEBUG] loadSoftSkillCatalogStatus - Parsed JSON successfully:', data);
                    return data;
                } catch (parseError) {
                    console.error('[DEBUG] loadSoftSkillCatalogStatus - JSON parse error:', parseError);
                    console.error('[DEBUG] loadSoftSkillCatalogStatus - JSON parse error at position:', parseError.message);
                    throw new Error(`JSON.parse: ${parseError.message} - Response was: ${text.substring(0, 100)}`);
                }
            });
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Unable to load catalog status');
            }
            renderSoftSkillFilesTable(data.files || []);
            renderSoftSkillWarnings(data);
            if (tablesWrapper) {
                tablesWrapper.classList.remove('d-none');
            }
            statusEl.className = 'alert alert-success mb-3';
            statusEl.innerHTML = `<i class="fas fa-check me-1"></i>${(data.files || []).length} definition files detected`;
        })
        .catch(error => {
            console.error('[DEBUG] loadSoftSkillCatalogStatus - Error:', error);
            console.error('[DEBUG] loadSoftSkillCatalogStatus - Error stack:', error.stack);
            
            let errorMessage = error.message || 'Unable to load catalog status';
            let errorType = 'danger';
            
            // Provide more specific error messages
            if (error.message.includes('Authentication required')) {
                errorMessage = 'Your session has expired. Please refresh the page and log in again.';
                errorType = 'warning';
            } else if (error.message.includes('Access denied')) {
                errorMessage = 'You do not have permission to access this feature.';
                errorType = 'warning';
            } else if (error.message.includes('HTML response')) {
                errorMessage = 'Server returned HTML instead of JSON. Please check if you are logged in.';
                errorType = 'warning';
            } else if (error.message.includes('JSON.parse')) {
                errorMessage = 'Invalid JSON response from server. Check browser console for details.';
            }
            
            statusEl.className = `alert alert-${errorType} mb-3`;
            statusEl.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i>${escapeHtml(errorMessage)}`;
        });
}

function renderSoftSkillFilesTable(files) {
    const container = document.getElementById('softSkillFilesTable');
    if (!container) return;
    
    if (!files.length) {
        container.innerHTML = '<div class="alert alert-light mb-0">No soft skill JSON files detected yet.</div>';
        return;
    }
    
    const rows = files.map(file => {
        const assignments = Array.isArray(file.assignments) && file.assignments.length
            ? file.assignments.map(item => `<span class="badge bg-primary text-white me-1 mb-1">${escapeHtml(item.name)}</span>`).join(' ')
            : '<span class="text-muted">Not linked to any competency</span>';
        const updated = file.last_modified ? new Date(file.last_modified).toLocaleString() : 'Unknown';
        const statusBadge = file.file_exists
            ? '<span class="badge bg-success">On disk</span>'
            : '<span class="badge bg-warning text-dark">Missing</span>';
        const filePath = file.file_path ? `<code>${escapeHtml(file.file_path)}</code>` : '<em>n/a</em>';
        const escapedKey = escapeHtml(file.competency_key).replace(/'/g, '\\\'');
        
        // Special handling for orphaned files
        const orphanedBadge = file.orphaned
            ? '<span class="badge bg-danger ms-1">Orphaned</span>'
            : '';
        const orphanedClass = file.orphaned ? 'table-danger' : '';
        const orphanButtons = [];
        if (softSkillImportEnabled) {
            orphanButtons.push(`<button type="button" class="btn btn-outline-success" onclick="openSoftSkillImportModal('${escapedKey}')" title="Import this JSON into competencies">
                    <i class="fas fa-database"></i>
                </button>`);
        }
        orphanButtons.push(`<button type="button" class="btn btn-outline-danger" onclick="deleteOrphanedJsonFile('${escapedKey}')" title="Delete orphaned JSON file">
                    <i class="fas fa-trash"></i>
                </button>`);
        const orphanedActions = `<div class="btn-group btn-group-sm">${orphanButtons.join('')}</div>`;
        const editButton = `
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewSoftSkillLevels(null, '${escapedKey}')">
                <i class="fas fa-pen me-1"></i>Edit JSON
            </button>`;
        const actions = file.orphaned ? orphanedActions : editButton;
        
        return `
            <tr class="${orphanedClass}">
                <td><code>${escapeHtml(file.competency_key)}</code></td>
                <td>${escapeHtml(file.name || '')}${orphanedBadge}</td>
                <td>${assignments}</td>
                <td>${filePath}<br>${statusBadge}</td>
                <td>${escapeHtml(updated)}</td>
                <td class="text-end">
                    ${actions}
                </td>
            </tr>
        `;
    }).join('');
    
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Name</th>
                        <th>Assignments</th>
                        <th>File</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderSoftSkillWarnings(status) {
    const container = document.getElementById('softSkillInconsistencies');
    if (!container) return;
    
    const missing = status.missing_definitions || [];
    const unassigned = status.unassigned_definitions || [];
    
    if (!missing.length && !unassigned.length) {
        container.classList.add('d-none');
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    if (missing.length) {
        const items = missing.map(item => {
            const links = (item.assignments || []).map(assign => escapeHtml(assign.name)).join(', ') || 'Unlinked competency';
            return `<li><code>${escapeHtml(item.competency_key)}</code> &mdash; ${links}</li>`;
        }).join('');
        html += `<div class="alert alert-danger"><strong>Missing JSON files:</strong><ul class="mb-0">${items}</ul></div>`;
    }
    
    if (unassigned.length) {
        const items = unassigned.map(key => `<code>${escapeHtml(key)}</code>`).join(', ');
        html += `<div class="alert alert-warning mt-3"><strong>Unassigned definition files:</strong> ${items || 'None'}</div>`;
    }
    
    container.innerHTML = html;
    container.classList.remove('d-none');
}

function setCompetencyImportFeedback(message, variant = 'info') {
    const feedback = document.getElementById('competencyImportFeedback');
    if (!feedback) return;
    
    feedback.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning', 'alert-danger');
    feedback.classList.add(`alert-${variant}`);
    feedback.innerHTML = message;
}

function safeCompetencyImportMetric(value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

function formatCompetencyImportSummary(data) {
    const summary = [
        `<strong>Imported:</strong> ${safeCompetencyImportMetric(data.imported)}`,
        `<strong>Updated:</strong> ${safeCompetencyImportMetric(data.updated)}`,
        `<strong>Skipped:</strong> ${safeCompetencyImportMetric(data.skipped)}`
    ].join('<br>');
    
    if (Array.isArray(data.errors) && data.errors.length) {
        const issues = data.errors.slice(0, 5).map(error => `<li>${error}</li>`).join('');
        return `${summary}<hr class="my-2"><strong>Issues</strong><ul class="mb-0">${issues}</ul>`;
    }
    
    return summary;
}

function importCompetencies() {
    console.log('[DEBUG] importCompetencies called at:', new Date().toISOString());
    const fileInput = document.getElementById('competencyCsvFile');
    if (!fileInput || !fileInput.files[0]) {
        setCompetencyImportFeedback('Please select a CSV file to import.', 'warning');
        return;
    }
    
    const importButton = document.getElementById('competencyImportButton');
    const originalButtonLabel = importButton ? importButton.innerHTML : '';
    const restoreButton = () => {
        if (importButton) {
            importButton.disabled = false;
            importButton.innerHTML = originalButtonLabel || 'Import';
        }
    };
    
    if (importButton) {
        if (importButton.disabled) {
            console.log('[DEBUG] importCompetencies - Button already disabled, ignoring double click');
            return;
        }
        importButton.disabled = true;
        importButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing';
        console.log('[DEBUG] importCompetencies - Button disabled, starting import process');
    }
    
    setCompetencyImportFeedback('Uploading and validating competency catalog...', 'info');
    
    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('/api/import_competencies.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const variant = Array.isArray(data.errors) && data.errors.length ? 'warning' : 'success';
                setCompetencyImportFeedback(formatCompetencyImportSummary(data), variant);
                setTimeout(() => location.reload(), 1200);
            } else {
                const message = data.message ? `Import failed: ${data.message}` : 'Import failed. Please review the CSV file.';
                setCompetencyImportFeedback(message, 'danger');
            }
            restoreButton();
        })
        .catch(error => {
            console.error('Error importing competencies:', error);
            setCompetencyImportFeedback('Unexpected error importing competencies. Please try again.', 'danger');
            restoreButton();
        });
}

function setSoftSkillImportFeedback(message, variant = 'info') {
    const feedback = document.getElementById('softSkillImportFeedback');
    if (!feedback) return;
    
    if (!message) {
        feedback.classList.add('d-none');
        feedback.innerHTML = '';
        return;
    }
    
    feedback.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning', 'alert-danger');
    feedback.classList.add(`alert-${variant}`);
    feedback.innerHTML = message;
}

function setSoftSkillImportSelection(competencyKey = '') {
    const selection = document.getElementById('softSkillImportSelection');
    const keyInput = document.getElementById('importSoftSkillJsonKey');
    if (keyInput && competencyKey !== undefined) {
        keyInput.value = competencyKey || '';
    }
    if (!selection) return;
    
    if (!competencyKey) {
        selection.className = 'alert alert-warning small mb-0';
        selection.innerHTML = 'Select an orphaned JSON entry from the catalog list to import it.';
        return;
    }
    
    selection.className = 'alert alert-success small mb-0';
    selection.innerHTML = `<strong>File selected:</strong> <code>${escapeHtml(competencyKey)}</code>`;
}

function openSoftSkillImportModal(competencyKey) {
    if (!competencyKey) {
        alert('Unable to determine which JSON file to import.');
        return;
    }
    
    setSoftSkillImportSelection(competencyKey);
    setSoftSkillImportFeedback('');
    
    const modalEl = document.getElementById('importSoftSkillJsonModal');
    if (!modalEl) {
        alert('Import modal not available. Please reload the page.');
        return;
    }
    
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function handleSoftSkillJsonImport(event) {
    event.preventDefault();
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) {
        return;
    }
    
    if (submitButton.disabled) {
        return;
    }
    
    const originalLabel = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing...';
    
    const keyInput = document.getElementById('importSoftSkillJsonKey');
    const competencyKey = keyInput ? keyInput.value.trim() : '';
    if (!competencyKey) {
        setSoftSkillImportFeedback('Select an orphaned JSON file from the catalog list before importing.', 'warning');
        submitButton.disabled = false;
        submitButton.innerHTML = originalLabel;
        return;
    }

    setSoftSkillImportFeedback('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing soft skill...', 'info');
    
    const formData = new FormData(form);
    
    fetch('/api/soft_skill_levels.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Unable to import soft skills from JSON catalog.');
            }
            
            const summaryParts = [
                `<strong>Created:</strong> ${safeCompetencyImportMetric(data.imported)}`,
                `<strong>Skipped:</strong> ${safeCompetencyImportMetric(data.skipped)}`
            ];
            
            let message = summaryParts.join('<br>');
            const hasErrors = Array.isArray(data.errors) && data.errors.length;
            if (hasErrors) {
                const issues = data.errors.slice(0, 5).map(error => `<li>${escapeHtml(error)}</li>`).join('');
                message += `<hr class="my-2"><strong>Notes</strong><ul class="mb-0">${issues}</ul>`;
            }
            
            setSoftSkillImportFeedback(`<strong>Catalog import completed.</strong><br>${message}`, hasErrors ? 'warning' : 'success');
            setTimeout(() => window.location.reload(), 1500);
        })
        .catch(error => {
            console.error('Error importing soft skill catalog:', error);
            setSoftSkillImportFeedback(escapeHtml(error.message || 'Error importing soft skill definitions.'), 'danger');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalLabel;
        });
}

function deleteOrphanedJsonFile(competencyKey) {
    if (!confirm(`Are you sure you want to delete the orphaned JSON file for "${competencyKey}"? This action cannot be undone.`)) {
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    if (!csrfToken) {
        alert('Security token missing. Please refresh the page and try again.');
        return;
    }

    fetch('/api/soft_skill_levels.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            competency_key: competencyKey,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadSoftSkillCatalogStatus(true);
        } else {
            alert('Error deleting orphaned JSON file: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting orphaned JSON file:', error);
        alert('Error deleting orphaned JSON file. Please try again.');
    });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[match]);
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
