<?php
/**
 * Job Templates Management
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';
require_once __DIR__ . '/../../classes/CompanyKPI.php';
require_once __DIR__ . '/../../classes/Competency.php';
require_once __DIR__ . '/../../classes/CompanyValues.php';
require_once __DIR__ . '/../../classes/Department.php';

// Require HR admin authentication
requireAuth();
if (!hasPermission('*')) {
    setFlashMessage('You do not have permission to access this page.', 'error');
    redirect('/dashboard.php');
}

// Initialize classes
$jobTemplateClass = new JobTemplate();
$kpiClass = new CompanyKPI();
$competencyClass = new Competency();
$valuesClass = new CompanyValues();
$departmentClass = new Department();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) || (
        !empty($_SERVER['CONTENT_TYPE']) &&
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
    );
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        } else {
            setFlashMessage('Invalid security token.', 'error');
            redirect('/admin/job_templates.php');
        }
    }
    
    $action = $_POST['action'] ?? '';
    
    // Debugging: Log the action being processed
    error_log("Processing action: " . $action);
    
    switch ($action) {
        case 'create_template':
            try {
                $templateData = [
                    'position_title' => sanitizeInput($_POST['position_title']),
                    'department' => sanitizeInput($_POST['department']),
                    'description' => sanitizeInput($_POST['description']),
                    'created_by' => $_SESSION['user_id']
                ];
                
                $templateId = $jobTemplateClass->createJobTemplate($templateData);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Job template created successfully.', 'redirect' => '/admin/job_templates.php?edit=' . $templateId]);
                    exit;
                } else {
                    setFlashMessage('Job template created successfully.', 'success');
                    redirect('/admin/job_templates.php?edit=' . $templateId);
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error creating job template: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error creating job template: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'update_template':
            try {
                $templateId = (int)$_POST['template_id'];
                $templateData = [
                    'position_title' => sanitizeInput($_POST['position_title']),
                    'department' => sanitizeInput($_POST['department']),
                    'description' => sanitizeInput($_POST['description'])
                ];
                
                $jobTemplateClass->updateJobTemplate($templateId, $templateData);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Job template updated successfully.']);
                    exit;
                } else {
                    setFlashMessage('Job template updated successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating job template: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error updating job template: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'delete_template':
            try {
                $templateId = (int)$_POST['template_id'];
                $jobTemplateClass->deleteJobTemplate($templateId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Job template deleted successfully.']);
                    exit;
                } else {
                    setFlashMessage('Job template deleted successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error deleting job template: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error deleting job template: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'add_kpi':
            try {
                $templateId = (int)$_POST['template_id'];
                $kpiId = (int)$_POST['kpi_id'];
                $targetValue = (float)$_POST['target_value'];
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addKPIToTemplate($templateId, $kpiId, $targetValue, $weight);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'KPI added to template successfully.',
                        'new_item_id' => $kpiId,
                        'section' => 'kpis'
                    ]);
                    exit;
                } else {
                    setFlashMessage('KPI added to template successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error adding KPI: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error adding KPI: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'add_competency':
            try {
                $templateId = (int)$_POST['template_id'];
                $competencyId = (int)$_POST['competency_id'];
                $requiredLevel = sanitizeInput($_POST['required_level']);
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addCompetencyToTemplate($templateId, $competencyId, $requiredLevel, $weight);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Competency added to template successfully.',
                        'new_item_id' => $competencyId,
                        'section' => 'competencies'
                    ]);
                    exit;
                } else {
                    setFlashMessage('Competency added to template successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error adding competency: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error adding competency: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'add_responsibility':
            try {
                $templateId = (int)$_POST['template_id'];
                $responsibilityText = sanitizeInput($_POST['responsibility_text']);
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $weight = (float)($_POST['weight'] ?? 100);
                
                $responsibilityId = $jobTemplateClass->addResponsibilityToTemplate($templateId, $responsibilityText, $sortOrder, $weight);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Responsibility added to template successfully.',
                        'new_item_id' => $responsibilityId,
                        'section' => 'responsibilities'
                    ]);
                    exit;
                } else {
                    setFlashMessage('Responsibility added to template successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error adding responsibility: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error adding responsibility: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'add_value':
            try {
                $templateId = (int)$_POST['template_id'];
                $valueId = (int)$_POST['value_id'];
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addValueToTemplate($templateId, $valueId, $weight);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Company value added to template successfully.',
                        'new_item_id' => $valueId,
                        'section' => 'values'
                    ]);
                    exit;
                } else {
                    setFlashMessage('Company value added to template successfully.', 'success');
                }
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error adding company value: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error adding company value: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        // Handle removal actions
        case 'remove_kpi':
            try {
                $templateId = (int)$_POST['template_id'];
                $kpiId = (int)$_POST['kpi_id'];
                $jobTemplateClass->removeKPIFromTemplate($templateId, $kpiId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'KPI removed from template successfully.']);
                    exit;
                } else {
                    setFlashMessage('KPI removed from template successfully.', 'success');
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error removing KPI: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error removing KPI: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'remove_competency':
            try {
                $templateId = (int)$_POST['template_id'];
                $competencyId = (int)$_POST['competency_id'];
                $jobTemplateClass->removeCompetencyFromTemplate($templateId, $competencyId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Competency removed from template successfully.']);
                    exit;
                } else {
                    setFlashMessage('Competency removed from template successfully.', 'success');
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error removing competency: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error removing competency: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'remove_responsibility':
            try {
                $responsibilityId = (int)$_POST['responsibility_id'];
                error_log("Attempting to remove responsibility ID: " . $responsibilityId);
                $jobTemplateClass->removeResponsibility($responsibilityId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Responsibility removed from template successfully.']);
                    exit;
                } else {
                    setFlashMessage('Responsibility removed from template successfully.', 'success');
                }
            } catch (Exception $e) {
                error_log("Error removing responsibility: " . $e->getMessage());
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error removing responsibility: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error removing responsibility: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'remove_value':
            try {
                $templateId = (int)$_POST['template_id'];
                $valueId = (int)$_POST['value_id'];
                $jobTemplateClass->removeValueFromTemplate($templateId, $valueId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Company value removed from template successfully.']);
                    exit;
                } else {
                    setFlashMessage('Company value removed from template successfully.', 'success');
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error removing company value: ' . $e->getMessage()]);
                    exit;
                } else {
                    setFlashMessage('Error removing company value: ' . $e->getMessage(), 'error');
                }
            }
            break;
            
        case 'refresh_section':
            try {
                $templateId = (int)$_POST['template_id'];
                $section = $_POST['section'];
                
                switch ($section) {
                    case 'kpis':
                        $data = $jobTemplateClass->getTemplateKPIs($templateId);
                        break;
                    case 'competencies':
                        $data = $jobTemplateClass->getTemplateCompetencies($templateId);
                        break;
                    case 'responsibilities':
                        $data = $jobTemplateClass->getTemplateResponsibilities($templateId);
                        break;
                    case 'values':
                        $data = $jobTemplateClass->getTemplateValues($templateId);
                        break;
                    default:
                        $data = [];
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            break;
    }
}

// Get data for display
$editTemplateId = $_GET['edit'] ?? null;
$templates = $jobTemplateClass->getJobTemplates();

$editTemplate = null;
$templateKPIs = [];
$templateCompetencies = [];
$templateResponsibilities = [];
$templateValues = [];

if ($editTemplateId) {
    $editTemplate = $jobTemplateClass->getCompleteJobTemplate($editTemplateId);
    if ($editTemplate) {
        $templateKPIs = $editTemplate['kpis'];
        $templateCompetencies = $editTemplate['competencies'];
        $templateResponsibilities = $editTemplate['responsibilities'];
        $templateValues = $editTemplate['values'];
    }
}

// Get available options for dropdowns
$availableKPIs = $kpiClass->getKPIs();
$availableCompetencies = $competencyClass->getCompetencies();
$availableValues = $valuesClass->getValues();
$competencyLevels = $competencyClass->getCompetencyLevels();

$pageTitle = 'Job Templates Management';
$pageHeader = true;
$pageDescription = 'Manage job position templates and their evaluation criteria';

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Job Templates Management</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                <i class="fas fa-plus me-2"></i>Create New Template
            </button>
        </div>
    </div>
</div>

<?php if (!$editTemplateId): ?>
<!-- Templates List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Job Position Templates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($templates)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No job templates found</h5>
                    <p class="text-muted">Create your first job template to get started.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Position Title</th>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Created By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($template['position_title']); ?></strong>
                                    <?php if ($template['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($template['description'], 0, 100)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($template['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $template['employee_count']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($template['created_by_username'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($template['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>)">
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

<?php else: ?>
<!-- Edit Template -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4>Edit Template: <?php echo htmlspecialchars($editTemplate['position_title']); ?></h4>
                <p class="text-muted mb-0">Configure evaluation criteria for this position</p>
            </div>
            <a href="/admin/job_templates.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>
</div>

<!-- Template Basic Info -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_template">
                    <input type="hidden" name="template_id" value="<?php echo $editTemplate['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position_title" class="form-label">Position Title</label>
                                <input type="text" class="form-control" id="position_title" name="position_title" 
                                       value="<?php echo htmlspecialchars($editTemplate['position_title']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $allDepartments = $departmentClass->getDepartments();
                                    foreach ($allDepartments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                            <?php echo ($editTemplate['department'] ?? '') == $dept['department_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($editTemplate['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Basic Information</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Template Sections -->
<div class="row">
    <!-- KPIs Section -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Desired Results (KPIs)</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addKPIModal">
                    <i class="fas fa-plus me-1"></i>Add KPI
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($templateKPIs)): ?>
                <p class="text-muted">No KPIs assigned to this template yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm" id="kpi-table">
                        <thead>
                            <tr>
                                <th>KPI Name</th>
                                <th>Category</th>
                                <th>Target Value</th>
                                <th>Unit</th>
                                <th>Weight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templateKPIs as $kpi): ?>
                            <tr data-id="<?php echo $kpi['kpi_id']; ?>" data-kpi-id="<?php echo $kpi['kpi_id']; ?>">
                                <td><?php echo htmlspecialchars($kpi['kpi_name']); ?></td>
                                <td><?php echo htmlspecialchars($kpi['category']); ?></td>
                                <td><?php echo number_format($kpi['target_value'], 2); ?></td>
                                <td><?php echo htmlspecialchars($kpi['measurement_unit']); ?></td>
                                <td><?php echo number_format($kpi['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeItem('kpi', <?php echo $kpi['kpi_id']; ?>); return false;">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
    
    <!-- Competencies Section -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Skills, Knowledge, and Competencies</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCompetencyModal">
                    <i class="fas fa-plus me-1"></i>Add Competency
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($templateCompetencies)): ?>
                <p class="text-muted">No competencies assigned to this template yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm" id="competency-table">
                        <thead>
                            <tr>
                                <th>Competency</th>
                                <th>Category</th>
                                <th>Required Level</th>
                                <th>Type</th>
                                <th>Weight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templateCompetencies as $competency): ?>
                            <tr data-competency-id="<?php echo $competency['competency_id']; ?>">
                                <td><?php echo htmlspecialchars($competency['competency_name']); ?></td>
                                <td><?php echo htmlspecialchars($competency['category_name']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo ucfirst($competency['required_level']); ?></span>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $competency['competency_type'])); ?></td>
                                <td><?php echo number_format($competency['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeCompetency(<?php echo $competency['competency_id']; ?>); return false;">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
    
    <!-- Responsibilities Section -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Key Responsibilities</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addResponsibilityModal">
                    <i class="fas fa-plus me-1"></i>Add Responsibility
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($templateResponsibilities)): ?>
                <p class="text-muted">No responsibilities defined for this template yet.</p>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($templateResponsibilities as $responsibility): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start" data-responsibility-id="<?php echo $responsibility['id']; ?>">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Responsibility #<?php echo $responsibility['sort_order']; ?></div>
                            <?php echo htmlspecialchars($responsibility['responsibility_text']); ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-2"><?php echo number_format($responsibility['weight_percentage'], 1); ?>%</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeResponsibility(<?php echo $responsibility['id']; ?>); return false;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Company Values Section -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Living Our Values</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addValueModal">
                    <i class="fas fa-plus me-1"></i>Add Value
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($templateValues)): ?>
                <p class="text-muted">No company values assigned to this template yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm" id="value-table">
                        <thead>
                            <tr>
                                <th>Value</th>
                                <th>Description</th>
                                <th>Weight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templateValues as $value): ?>
                            <tr data-value-id="<?php echo $value['value_id']; ?>">
                                <td><strong><?php echo htmlspecialchars($value['value_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($value['description']); ?></td>
                                <td><?php echo number_format($value['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeValue(<?php echo $value['value_id']; ?>); return false;">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
<?php endif; ?>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Job Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create_template">
                    
                    <div class="mb-3">
                        <label for="new_position_title" class="form-label">Position Title</label>
                        <input type="text" class="form-control" id="new_position_title" name="position_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_department" class="form-label">Department</label>
                        <select class="form-select" id="new_department" name="department">
                            <option value="">Select Department</option>
                            <?php 
                            $allDepartments = $departmentClass->getDepartments();
                            foreach ($allDepartments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_description" class="form-label">Description</label>
                        <textarea class="form-control" id="new_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editTemplateId): ?>
<!-- Add KPI Modal -->
<div class="modal fade" id="addKPIModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add KPI to Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_kpi">
                    <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
                    
                    <div class="mb-3">
                        <label for="kpi_id" class="form-label">Select KPI</label>
                        <select class="form-select" id="kpi_id" name="kpi_id" required>
                            <option value="">Choose a KPI...</option>
                            <?php foreach ($availableKPIs as $kpi): ?>
                            <option value="<?php echo $kpi['id']; ?>">
                                <?php echo htmlspecialchars($kpi['kpi_name'] . ' (' . $kpi['category'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="target_value" class="form-label">Target Value</label>
                        <input type="number" step="0.01" class="form-control" id="target_value" name="target_value" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kpi_weight" class="form-label">Weight (%)</label>
                        <input type="number" step="0.1" class="form-control" id="kpi_weight" name="weight" value="100" min="0" max="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Competency Modal -->
<div class="modal fade" id="addCompetencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Competency to Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_competency">
                    <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
                    
                    <div class="mb-3">
                        <label for="competency_id" class="form-label">Select Competency</label>
                        <select class="form-select" id="competency_id" name="competency_id" required>
                            <option value="">Choose a competency...</option>
                            <?php foreach ($availableCompetencies as $competency): ?>
                            <option value="<?php echo $competency['id']; ?>">
                                <?php echo htmlspecialchars($competency['competency_name'] . ' (' . $competency['category_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="required_level" class="form-label">Required Level</label>
                        <select class="form-select" id="required_level" name="required_level" required>
                            <?php foreach ($competencyLevels as $level => $label): ?>
                            <option value="<?php echo $level; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="competency_weight" class="form-label">Weight (%)</label>
                        <input type="number" step="0.1" class="form-control" id="competency_weight" name="weight" value="100" min="0" max="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Competency</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Responsibility Modal -->
<div class="modal fade" id="addResponsibilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Responsibility to Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_responsibility">
                    <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
                    
                    <div class="mb-3">
                        <label for="responsibility_text" class="form-label">Responsibility Description</label>
                        <textarea class="form-control" id="responsibility_text" name="responsibility_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo count($templateResponsibilities) + 1; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="responsibility_weight" class="form-label">Weight (%)</label>
                        <input type="number" step="0.1" class="form-control" id="responsibility_weight" name="weight" value="100" min="0" max="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Responsibility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Value Modal -->
<div class="modal fade" id="addValueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Company Value to Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_value">
                    <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
                    
                    <div class="mb-3">
                        <label for="value_id" class="form-label">Select Company Value</label>
                        <select class="form-select" id="value_id" name="value_id" required>
                            <option value="">Choose a value...</option>
                            <?php foreach ($availableValues as $value): ?>
                            <option value="<?php echo $value['id']; ?>">
                                <?php echo htmlspecialchars($value['value_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="value_weight" class="form-label">Weight (%)</label>
                        <input type="number" step="0.1" class="form-control" id="value_weight" name="weight" value="100" min="0" max="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Value</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Alerts container -->
<div id="alerts-container"></div>

<script>
// Enhanced AJAX request function with scroll preservation
function ajaxRequest(url, data, method = 'POST', callback = null) {
    console.log('AJAX Request to:', url);
    console.log('AJAX Data:', data);
    
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log('AJAX Response Status:', xhr.status);
            console.log('AJAX Response Text:', xhr.responseText);
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed Response:', response);
                    if (callback) callback(null, response);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Raw response text:', xhr.responseText);
                    if (callback) callback('Invalid JSON response: ' + e.message, null);
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                console.error('Response text:', xhr.responseText);
                if (callback) callback('HTTP Error: ' + xhr.status + ' - ' + xhr.responseText, null);
            }
        }
    };
    
    // Convert data to URL-encoded string
    const params = new URLSearchParams(data).toString();
    console.log('Sending params:', params);
    try {
        xhr.send(params);
    } catch (sendError) {
        console.error('Send Error:', sendError);
        if (callback) callback('Send Error: ' + sendError.message, null);
    }
}

// Enhanced form submission with scroll preservation and visual effects
function submitFormWithScrollPreservation(form, callback) {
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Add loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    }
    
    ajaxRequest(window.location.href, data, 'POST', function(error, response) {
        // Remove loading state
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
        }
        
        if (error) {
            showAlert('danger', 'Error: ' + error);
            if (callback) callback(error, null);
            return;
        }
        
        if (response && response.success) {
            showAlert('success', response.message || 'Operation completed successfully');
            
            // Add visual effects for new items
            if (response.new_item_id) {
                highlightNewItem(response.new_item_id);
            }
            
            // Close modal if open
            const modal = form.closest('.modal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            
            // Refresh specific sections without page reload
            refreshTemplateSection(response.section || 'all');
        } else {
            showAlert('danger', response.message || 'Operation failed');
        }
        
        if (callback) callback(null, response);
    });
}

// Function to highlight newly added items with visual effects
function highlightNewItem(itemId) {
    const item = document.querySelector(`[data-id="${itemId}"]`) ||
                 document.querySelector(`[data-kpi-id="${itemId}"]`) ||
                 document.querySelector(`[data-competency-id="${itemId}"]`) ||
                 document.querySelector(`[data-responsibility-id="${itemId}"]`) ||
                 document.querySelector(`[data-value-id="${itemId}"]`);
    
    if (item) {
        item.classList.add('new-item-highlight');
        item.style.animation = 'highlightNewItem 2s ease-in-out';
        
        // Remove highlight after animation
        setTimeout(() => {
            item.classList.remove('new-item-highlight');
            item.style.animation = '';
        }, 2000);
    }
}

// Function to refresh specific template sections
function refreshTemplateSection(section) {
    const templateId = <?php echo $editTemplateId; ?>;
    
    ajaxRequest(window.location.href, {
        'csrf_token': '<?php echo generateCSRFToken(); ?>',
        'action': 'refresh_section',
        'template_id': templateId,
        'section': section
    }, 'POST', function(error, response) {
        if (error) {
            showAlert('danger', 'Error: ' + error);
            return;
        }
        
        if (response && response.success) {
            // Update the specific section with new data
            updateSectionContent(section, response.data);
        }
    });
}

function updateSectionContent(section, data) {
    const sections = {
        'kpis': '#kpi-table tbody',
        'competencies': '#competency-table tbody',
        'responsibilities': '.list-group',
        'values': '#value-table tbody'
    };
    
    const selector = sections[section];
    const container = document.querySelector(selector);
    
    if (container) {
        // Clear existing content
        container.innerHTML = '';
        
        // Add new content
        data.forEach(item => {
            const row = createSectionRow(section, item);
            container.appendChild(row);
        });
        
        // Add visual highlight effect
        container.classList.add('new-item-highlight');
        setTimeout(() => {
            container.classList.remove('new-item-highlight');
        }, 2000);
    }
}

function createSectionRow(section, item) {
    const row = document.createElement('tr');
    
    switch (section) {
        case 'kpis':
            row.setAttribute('data-id', item.kpi_id);
            row.setAttribute('data-kpi-id', item.kpi_id);
            row.innerHTML = `
                <td>${escapeHtml(item.kpi_name)}</td>
                <td>${escapeHtml(item.category)}</td>
                <td>${parseFloat(item.target_value).toFixed(2)}</td>
                <td>${escapeHtml(item.measurement_unit)}</td>
                <td>${parseFloat(item.weight_percentage).toFixed(1)}%</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeItem('kpi', ${item.kpi_id}); return false;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            break;
            
        case 'competencies':
            row.setAttribute('data-competency-id', item.competency_id);
            row.innerHTML = `
                <td>${escapeHtml(item.competency_name)}</td>
                <td>${escapeHtml(item.category_name)}</td>
                <td>
                    <span class="badge bg-info">${ucfirst(item.required_level)}</span>
                </td>
                <td>${ucfirst(item.competency_type.replace('_', ' '))}</td>
                <td>${parseFloat(item.weight_percentage).toFixed(1)}%</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeItem('competency', ${item.competency_id}); return false;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            break;
            
        case 'values':
            row.setAttribute('data-value-id', item.value_id);
            row.innerHTML = `
                <td><strong>${escapeHtml(item.value_name)}</strong></td>
                <td>${escapeHtml(item.description)}</td>
                <td>${parseFloat(item.weight_percentage).toFixed(1)}%</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeItem('value', ${item.value_id}); return false;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            break;
            
        case 'responsibilities':
            // For responsibilities, we return a div element instead of a tr
            const div = document.createElement('div');
            div.className = 'list-group-item d-flex justify-content-between align-items-start';
            div.setAttribute('data-responsibility-id', item.id);
            div.innerHTML = `
                <div class="ms-2 me-auto">
                    <div class="fw-bold">Responsibility #${item.sort_order}</div>
                    ${escapeHtml(item.responsibility_text)}
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary rounded-pill me-2">${parseFloat(item.weight_percentage).toFixed(1)}%</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeItem('responsibility', ${item.id}); return false;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            return div;
    }
    
    return row;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to capitalize first letter
function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Enhanced form handling for all modals
function setupModalForms() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const forms = modal.querySelectorAll('form');
        forms.forEach(form => {
            // Store original button text
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
            }
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFormWithScrollPreservation(form);
            });
        });
    });
}

// Enhanced remove functions with visual feedback
function removeItem(type, id) {
    const messages = {
        'kpi': 'Are you sure you want to remove this KPI?',
        'competency': 'Are you sure you want to remove this competency?',
        'responsibility': 'Are you sure you want to remove this responsibility?',
        'value': 'Are you sure you want to remove this company value?'
    };
    
    if (confirm(messages[type] || 'Are you sure you want to remove this item?')) {
        const data = {
            'csrf_token': '<?php echo generateCSRFToken(); ?>',
            'action': `remove_${type}`,
            'template_id': <?php echo $editTemplateId; ?>
        };
        
        if (type === 'responsibility') {
            data['responsibility_id'] = id;
        } else {
            data[`${type}_id`] = id;
        }
        
        ajaxRequest(window.location.href, data, 'POST', function(error, response) {
            if (error) {
                showAlert('danger', 'Error: ' + error);
                return;
            }
            
            if (response && response.success) {
                showAlert('success', response.message || 'Item removed successfully');
                
                // Remove item from UI with fade effect
                const item = document.querySelector(`[data-${type}-id="${id}"]`);
                if (item) {
                    item.style.transition = 'opacity 0.3s ease';
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.remove();
                    }, 300);
                }
            } else {
                showAlert('danger', response.message || 'Failed to remove item');
            }
        });
    }
}

// Initialize enhanced functionality
document.addEventListener('DOMContentLoaded', function() {
    setupModalForms();
    
    // Update existing remove buttons to use new functions
    const removeButtons = document.querySelectorAll('.btn-remove');
    removeButtons.forEach(button => {
        const onclick = button.getAttribute('onclick');
        if (onclick) {
            // Update onclick handlers to use new removeItem function
            const match = onclick.match(/remove(\w+)\((\d+)\)/);
            if (match) {
                const type = match[1].toLowerCase();
                const id = match[2];
                button.setAttribute('onclick', `removeItem('${type}', ${id})`);
            }
        }
    });
});

// Function to show alerts
function showAlert(type, message) {
    const alertContainer = document.getElementById('alerts-container') ||
                          document.querySelector('.container-fluid') ||
                          document.body;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Enhanced deleteTemplate function with AJAX
function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this job template? This action cannot be undone.')) {
        const data = {
            'csrf_token': '<?php echo generateCSRFToken(); ?>',
            'action': 'delete_template',
            'template_id': templateId
        };
        
        ajaxRequest(window.location.href, data, 'POST', function(error, response) {
            if (error) {
                console.error('Error deleting template:', error);
                showAlert('danger', 'Error deleting template: ' + error);
                return;
            }
            
            if (response && response.success) {
                showAlert('success', response.message || 'Template deleted successfully');
                // Reload the page to show updated content
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('danger', response.message || 'Failed to delete template');
            }
        });
    }
}

// Enhanced removeKPI function with AJAX
function removeKPI(kpiId) {
    removeItem('kpi', kpiId);
}

// Enhanced removeCompetency function with AJAX
function removeCompetency(competencyId) {
    removeItem('competency', competencyId);
}

// Enhanced removeResponsibility function with AJAX
function removeResponsibility(responsibilityId) {
    removeItem('responsibility', responsibilityId);
}

// Enhanced removeValue function with AJAX
function removeValue(valueId) {
    removeItem('value', valueId);
}

// Remove the conflicting scroll position management that was causing strange behavior
// The saveScrollPosition() and restoreScrollPosition() functions in the AJAX handlers are sufficient
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
