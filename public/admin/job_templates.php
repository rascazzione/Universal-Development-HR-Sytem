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
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        redirect('/admin/job_templates.php');
    }
    
    $action = $_POST['action'] ?? '';
    
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
                setFlashMessage('Job template created successfully.', 'success');
                redirect('/admin/job_templates.php?edit=' . $templateId);
                
            } catch (Exception $e) {
                setFlashMessage('Error creating job template: ' . $e->getMessage(), 'error');
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
                setFlashMessage('Job template updated successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error updating job template: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'delete_template':
            try {
                $templateId = (int)$_POST['template_id'];
                $jobTemplateClass->deleteJobTemplate($templateId);
                setFlashMessage('Job template deleted successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error deleting job template: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'add_kpi':
            try {
                $templateId = (int)$_POST['template_id'];
                $kpiId = (int)$_POST['kpi_id'];
                $targetValue = (float)$_POST['target_value'];
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addKPIToTemplate($templateId, $kpiId, $targetValue, $weight);
                setFlashMessage('KPI added to template successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error adding KPI: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'add_competency':
            try {
                $templateId = (int)$_POST['template_id'];
                $competencyId = (int)$_POST['competency_id'];
                $requiredLevel = sanitizeInput($_POST['required_level']);
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addCompetencyToTemplate($templateId, $competencyId, $requiredLevel, $weight);
                setFlashMessage('Competency added to template successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error adding competency: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'add_responsibility':
            try {
                $templateId = (int)$_POST['template_id'];
                $responsibilityText = sanitizeInput($_POST['responsibility_text']);
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addResponsibilityToTemplate($templateId, $responsibilityText, $sortOrder, $weight);
                setFlashMessage('Responsibility added to template successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error adding responsibility: ' . $e->getMessage(), 'error');
            }
            break;
            
        case 'add_value':
            try {
                $templateId = (int)$_POST['template_id'];
                $valueId = (int)$_POST['value_id'];
                $weight = (float)($_POST['weight'] ?? 100);
                
                $jobTemplateClass->addValueToTemplate($templateId, $valueId, $weight);
                setFlashMessage('Company value added to template successfully.', 'success');
                
            } catch (Exception $e) {
                setFlashMessage('Error adding company value: ' . $e->getMessage(), 'error');
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
                    <table class="table table-sm">
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
                            <tr>
                                <td><?php echo htmlspecialchars($kpi['kpi_name']); ?></td>
                                <td><?php echo htmlspecialchars($kpi['category']); ?></td>
                                <td><?php echo number_format($kpi['target_value'], 2); ?></td>
                                <td><?php echo htmlspecialchars($kpi['measurement_unit']); ?></td>
                                <td><?php echo number_format($kpi['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeKPI(<?php echo $kpi['kpi_id']; ?>)">
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
                    <table class="table table-sm">
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
                            <tr>
                                <td><?php echo htmlspecialchars($competency['competency_name']); ?></td>
                                <td><?php echo htmlspecialchars($competency['category_name']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo ucfirst($competency['required_level']); ?></span>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $competency['competency_type'])); ?></td>
                                <td><?php echo number_format($competency['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCompetency(<?php echo $competency['competency_id']; ?>)">
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
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Responsibility #<?php echo $responsibility['sort_order']; ?></div>
                            <?php echo htmlspecialchars($responsibility['responsibility_text']); ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-2"><?php echo number_format($responsibility['weight_percentage'], 1); ?>%</span>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeResponsibility(<?php echo $responsibility['id']; ?>)">
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
                    <table class="table table-sm">
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
                            <tr>
                                <td><strong><?php echo htmlspecialchars($value['value_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($value['description']); ?></td>
                                <td><?php echo number_format($value['weight_percentage'], 1); ?>%</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeValue(<?php echo $value['value_id']; ?>)">
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

<script>
function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this job template? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_template">
            <input type="hidden" name="template_id" value="${templateId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeKPI(kpiId) {
    if (confirm('Are you sure you want to remove this KPI from the template?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="remove_kpi">
            <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
            <input type="hidden" name="kpi_id" value="${kpiId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeCompetency(competencyId) {
    if (confirm('Are you sure you want to remove this competency from the template?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="remove_competency">
            <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
            <input type="hidden" name="competency_id" value="${competencyId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeResponsibility(responsibilityId) {
    if (confirm('Are you sure you want to remove this responsibility from the template?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="remove_responsibility">
            <input type="hidden" name="responsibility_id" value="${responsibilityId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeValue(valueId) {
    if (confirm('Are you sure you want to remove this company value from the template?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="remove_value">
            <input type="hidden" name="template_id" value="<?php echo $editTemplateId; ?>">
            <input type="hidden" name="value_id" value="${valueId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
