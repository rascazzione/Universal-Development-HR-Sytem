<?php
/**
 * Give Feedback Page
 * Growth Evidence System - Continuous Performance Management
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/GrowthEvidenceJournal.php';
require_once __DIR__ . '/../../classes/MediaManager.php';

// Require authentication
requireAuth();

// Detect if this is self-feedback
$currentUserEmployeeId = $_SESSION['employee_id'] ?? null;
$isSelfFeedback = ($employeeId == $currentUserEmployeeId);

// If it's NOT self-feedback, only allow managers and HR admins
if (!$isSelfFeedback && !in_array($_SESSION['user_role'], ['manager', 'hr_admin'])) {
    setFlashMessage('Solo puedes dar feedback a ti mismo o, si eres manager, a tu equipo.', 'error');
    redirect('/dashboard.php');
}

$pageTitle = 'Give Feedback';
$pageHeader = true;
$pageDescription = 'Provide feedback to your team members through the Growth Evidence System';

// Initialize classes
$employeeClass = new Employee();
$journalClass = new GrowthEvidenceJournal();
$mediaManager = new MediaManager();

// Get employee ID from URL
$employeeId = $_GET['employee_id'] ?? null;
if (!$employeeId) {
    setFlashMessage('Employee ID is required.', 'error');
    redirect('/employees/list.php');
}

// Get employee details
$employee = $employeeClass->getEmployeeById($employeeId);
if (!$employee) {
    setFlashMessage('Employee not found.', 'error');
    redirect('/employees/list.php');
}

// Check if current user can give feedback to this employee
if (!$isSelfFeedback) {
    // If NOT self-feedback, validate that it's a manager of the employee
    if ($_SESSION['user_role'] === 'manager' && $employee['manager_id'] != $currentUserEmployeeId) {
        setFlashMessage('Solo puedes dar feedback a tus subordinados directos.', 'error');
        redirect('/employees/list.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['content', 'star_rating', 'dimension', 'entry_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Prepare entry data
        // For autofeedback, manager_id should equal employee_id
        $managerId = $isSelfFeedback ? $employeeId : $currentUserEmployeeId;
        
        $entryData = [
            'employee_id' => $employeeId,
            'manager_id' => $managerId,
            'content' => $_POST['content'],
            'star_rating' => (int)$_POST['star_rating'],
            'dimension' => $_POST['dimension'],
            'entry_date' => $_POST['entry_date']
        ];
        
        // Create evidence entry
        $entryId = $journalClass->createEntry($entryData);
        
        // Handle file uploads if any
        if (!empty($_FILES['attachments']['name'][0])) {
            $mediaManager = new MediaManager();
            foreach ($_FILES['attachments']['error'] as $key => $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['attachments']['name'][$key],
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key]
                    ];
                    
                    try {
                        $mediaManager->uploadFile($file, $entryId);
                    } catch (Exception $e) {
                        error_log("File upload error: " . $e->getMessage());
                        // Continue with other uploads even if one fails
                    }
                }
            }
        }
        
        setFlashMessage('Feedback submitted successfully!', 'success');
        redirect("/employees/view.php?id=$employeeId");
        
    } catch (Exception $e) {
        error_log("Create feedback error: " . $e->getMessage());
        setFlashMessage('Error submitting feedback: ' . $e->getMessage(), 'error');
    }
}

// Add custom CSS for autofeedback banner
$pageStylesheets = [
    '/assets/css/dashboard-job-template.css'
];

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($isSelfFeedback): ?>
        <!-- AUTOFEEDBACK Banner -->
        <div class="alert alert-info alert-self-feedback mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle fa-3x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">
                        ⭐ AUTOFEEDBACK - Reflexión Personal
                    </h5>
                    <p class="mb-0">
                        Estás documentando feedback sobre tu propio desempeño.
                        Esta es una oportunidad para reflexionar sobre tus logros,
                        aprendizajes y áreas de mejora.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php if ($isSelfFeedback): ?>
                        ⭐ Autofeedback - Reflexión Personal
                    <?php else: ?>
                        Give Feedback to <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                    <?php endif; ?>
                </h5>
                <small class="text-muted"><?php echo htmlspecialchars($employee['position'] ?? ''); ?> <?php echo htmlspecialchars($employee['department'] ? '· ' . $employee['department'] : ''); ?></small>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="feedbackForm">
                    <!-- Step 1: Dimension Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Step 1: Select Feedback Dimension</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="dimension" class="form-label fw-bold">Select Dimension *</label>
                                                <select class="form-select form-select-lg" id="dimension" name="dimension" required>
                                                    <option value="">Choose a dimension to provide context...</option>
                                                    <option value="responsibilities" <?php echo (($_POST['dimension'] ?? '') === 'responsibilities') ? 'selected' : ''; ?>>Key Responsibilities</option>
                                                    <option value="kpis" <?php echo (($_POST['dimension'] ?? '') === 'kpis') ? 'selected' : ''; ?>>KPIs (Key Performance Indicators)</option>
                                                    <option value="competencies" <?php echo (($_POST['dimension'] ?? '') === 'competencies') ? 'selected' : ''; ?>>Skills, Knowledge & Competencies</option>
                                                    <option value="values" <?php echo (($_POST['dimension'] ?? '') === 'values') ? 'selected' : ''; ?>>Company Values</option>
                                                </select>
                                                <div class="form-text">Select the dimension that best relates to your feedback. This will show relevant job context below.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Job Template Context (Dynamic) -->
                    <div class="row mb-4" id="jobTemplateContext" style="display: none;">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Step 2: Job Context - <span id="contextTitle">Select a dimension above</span></h6>
                                </div>
                                <div class="card-body">
                                    <div id="contextContent">
                                        <div class="text-center py-3">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="text-muted mt-2">Loading job template information...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Feedback Content -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Step 3: Write Your Feedback</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="content" class="form-label fw-bold">Feedback Content *</label>
                                        <textarea class="form-control" id="content" name="content" rows="6" required
                                                  placeholder="Describe the specific behavior, achievement, or area for improvement you're providing feedback on. Include concrete examples when possible."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                        <div class="form-text">Be specific and provide examples. This feedback will be used in performance evaluations.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Rating and Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-star me-2"></i>Step 4: Rating & Date</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="star_rating" class="form-label fw-bold">Rating (1-5 stars) *</label>
                                        <select class="form-select" id="star_rating" name="star_rating" required>
                                            <option value="">Select rating</option>
                                            <option value="1" <?php echo (($_POST['star_rating'] ?? '') === '1') ? 'selected' : ''; ?>>★☆☆☆☆ - Needs Improvement</option>
                                            <option value="2" <?php echo (($_POST['star_rating'] ?? '') === '2') ? 'selected' : ''; ?>>★★☆☆☆ - Below Average</option>
                                            <option value="3" <?php echo (($_POST['star_rating'] ?? '') === '3') ? 'selected' : ''; ?>>★★★☆☆ - Meets Expectations</option>
                                            <option value="4" <?php echo (($_POST['star_rating'] ?? '') === '4') ? 'selected' : ''; ?>>★★★★☆ - Exceeds Expectations</option>
                                            <option value="5" <?php echo (($_POST['star_rating'] ?? '') === '5') ? 'selected' : ''; ?>>★★★★★ - Outstanding</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="entry_date" class="form-label fw-bold">Feedback Date *</label>
                                        <input type="date" class="form-control" id="entry_date" name="entry_date"
                                               value="<?php echo htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Step 5: Attachments (Optional)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="attachments" class="form-label fw-bold">Supporting Documents</label>
                                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                                               accept="image/*,video/*,.pdf,.doc,.docx">
                                        <div class="form-text">You can attach images, videos, or documents to support your feedback.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guidelines and Rating Scale - Moved to bottom -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Feedback Guidelines & Rating Scale</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="fw-bold text-primary">Feedback Guidelines</h6>
                                            <ul class="small">
                                                <li><strong>Be Specific:</strong> Describe concrete behaviors or outcomes</li>
                                                <li><strong>Be Timely:</strong> Provide feedback close to when the event occurred</li>
                                                <li><strong>Be Balanced:</strong> Include both positive recognition and constructive feedback</li>
                                                <li><strong>Be Actionable:</strong> Suggest clear next steps when appropriate</li>
                                                <li><strong>Be Respectful:</strong> Focus on behaviors and outcomes, not personality traits</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold text-primary">Rating Scale</h6>
                                            <ul class="small">
                                                <li><strong>5 Stars:</strong> Exceptional performance, consistently exceeds expectations</li>
                                                <li><strong>4 Stars:</strong> Strong performance, exceeds expectations</li>
                                                <li><strong>3 Stars:</strong> Meets expectations consistently</li>
                                                <li><strong>2 Stars:</strong> Below expectations, needs improvement</li>
                                                <li><strong>1 Star:</strong> Significantly below expectations, requires immediate attention</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/employees/view.php?id=<?php echo $employeeId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Employee
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic job template context loading
document.addEventListener('DOMContentLoaded', function() {
    const dimensionSelect = document.getElementById('dimension');
    const jobTemplateContext = document.getElementById('jobTemplateContext');
    const contextTitle = document.getElementById('contextTitle');
    const contextContent = document.getElementById('contextContent');
    const employeeId = <?php echo $employeeId; ?>;
    
    // Dimension titles for display
    const dimensionTitles = {
        'responsibilities': 'Key Responsibilities',
        'kpis': 'Key Performance Indicators (KPIs)',
        'competencies': 'Skills, Knowledge & Competencies',
        'values': 'Company Values'
    };
    
    // Handle dimension change
    dimensionSelect.addEventListener('change', function() {
        const selectedDimension = this.value;
        
        if (!selectedDimension) {
            jobTemplateContext.style.display = 'none';
            return;
        }
        
        // Show loading state
        jobTemplateContext.style.display = 'block';
        contextTitle.textContent = dimensionTitles[selectedDimension];
        contextContent.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="text-muted mt-2">Loading job template information...</p>
            </div>
        `;
        
        // Fetch job template data
        fetch(`/api/job-template.php?employee_id=${employeeId}&dimension=${selectedDimension}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayJobTemplateContext(selectedDimension, data.job_template);
                } else {
                    showError(data.message || 'Failed to load job template information');
                }
            })
            .catch(error => {
                console.error('Error loading job template:', error);
                showError('Error loading job template information. Please try again.');
            });
    });
    
    // Display job template context based on dimension
    function displayJobTemplateContext(dimension, jobTemplateData) {
        let html = '';
        
        if (!jobTemplateData.template) {
            html = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No job template assigned to this employee. Contact HR to assign a job template for better feedback context.
                </div>
            `;
        } else {
            const template = jobTemplateData.template;
            
            switch (dimension) {
                case 'responsibilities':
                    html = displayResponsibilities(jobTemplateData.responsibilities || []);
                    break;
                case 'kpis':
                    html = displayKPIs(jobTemplateData.kpis || []);
                    break;
                case 'competencies':
                    html = displayCompetencies(jobTemplateData.competencies || []);
                    break;
                case 'values':
                    html = displayValues(jobTemplateData.values || []);
                    break;
            }
        }
        
        contextContent.innerHTML = html;
    }
    
    // Display responsibilities
    function displayResponsibilities(responsibilities) {
        if (responsibilities.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No responsibilities defined for this position.
                </div>
            `;
        }
        
        let html = `
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Reference:</strong> Use these responsibilities as context for your feedback. Consider how the employee's performance relates to these key areas.
            </div>
            <div class="list-group">
        `;
        
        responsibilities.forEach((resp, index) => {
            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Responsibility #${resp.sort_order || (index + 1)}</h6>
                        <small class="text-muted">Weight: ${resp.weight_percentage || 100}%</small>
                    </div>
                    <p class="mb-1">${escapeHtml(resp.responsibility_text)}</p>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    // Display KPIs
    function displayKPIs(kpis) {
        if (kpis.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No KPIs defined for this position.
                </div>
            `;
        }
        
        let html = `
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Reference:</strong> Use these KPIs as context for your feedback. Consider the employee's performance against these targets.
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>KPI</th>
                            <th>Category</th>
                            <th>Target</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        kpis.forEach(kpi => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(kpi.kpi_name)}</strong></td>
                    <td><span class="badge bg-secondary">${escapeHtml(kpi.category)}</span></td>
                    <td>${parseFloat(kpi.target_value).toFixed(2)} ${escapeHtml(kpi.measurement_unit)}</td>
                    <td>${parseFloat(kpi.weight_percentage).toFixed(1)}%</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        return html;
    }
    
    // Display competencies
    function displayCompetencies(competencies) {
        if (competencies.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No competencies defined for this position.
                </div>
            `;
        }
        
        let html = `
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Reference:</strong> Use these competencies as context for your feedback. Consider the employee's demonstration of these skills and behaviors.
            </div>
            <div class="row">
        `;
        
        competencies.forEach(comp => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">${escapeHtml(comp.competency_name)}</h6>
                            <p class="card-text small">${escapeHtml(comp.description || '')}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-info">${ucfirst(comp.required_level)}</span>
                                <small class="text-muted">${escapeHtml(comp.category_name)}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    // Display company values
    function displayValues(values) {
        if (values.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No company values assigned to this position.
                </div>
            `;
        }
        
        let html = `
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Reference:</strong> Use these company values as context for your feedback. Consider how the employee demonstrates these values in their work.
            </div>
            <div class="row">
        `;
        
        values.forEach(value => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">${escapeHtml(value.value_name)}</h6>
                            <p class="card-text small">${escapeHtml(value.description || '')}</p>
                            <small class="text-muted">Weight: ${parseFloat(value.weight_percentage).toFixed(1)}%</small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    // Show error message
    function showError(message) {
        contextContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${escapeHtml(message)}
            </div>
        `;
    }
    
    // Helper functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function ucfirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    // Initialize form if dimension is pre-selected
    if (dimensionSelect.value) {
        dimensionSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
