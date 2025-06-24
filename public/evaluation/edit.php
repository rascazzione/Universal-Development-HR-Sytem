<?php
/**
 * Job Template-Based Evaluation Edit Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Evaluation.php';
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../../classes/JobTemplate.php';

// Helper function to get score labels
function getScoreLabel($score) {
    if ($score >= 4.5) return 'Excellent';
    if ($score >= 3.5) return 'Good';
    if ($score >= 2.5) return 'Satisfactory';
    if ($score >= 1.5) return 'Needs Improvement';
    return 'Unsatisfactory';
}

// Require authentication
requireAuth();

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? null;
if (!$evaluationId) {
    redirect('/evaluation/list.php');
}

// Initialize classes
$evaluationClass = new Evaluation();
$employeeClass = new Employee();
$periodClass = new EvaluationPeriod();
$jobTemplateClass = new JobTemplate();

// Get evaluation details
$evaluation = $evaluationClass->getJobTemplateEvaluation($evaluationId);
if (!$evaluation) {
    setFlashMessage('Evaluation not found.', 'error');
    redirect('/evaluation/list.php');
}

// LOG: Debug evaluation structure and job template data
error_log("EVALUATION DEBUG - ID: $evaluationId");
error_log("EVALUATION DEBUG - Job Template ID: " . ($evaluation['job_template_id'] ?? 'NULL'));
error_log("EVALUATION DEBUG - KPI Results Count: " . count($evaluation['kpi_results'] ?? []));
error_log("EVALUATION DEBUG - Competency Results Count: " . count($evaluation['competency_results'] ?? []));
error_log("EVALUATION DEBUG - Responsibility Results Count: " . count($evaluation['responsibility_results'] ?? []));
error_log("EVALUATION DEBUG - Value Results Count: " . count($evaluation['value_results'] ?? []));

// Check permissions
$userRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Only allow editing if user is the evaluator or HR admin, and evaluation is in draft status
if ($evaluation['status'] !== 'draft' || 
    ($userRole !== 'hr_admin' && $evaluation['evaluator_id'] != $currentUserId)) {
    setFlashMessage('You do not have permission to edit this evaluation.', 'error');
    redirect('/evaluation/view.php?id=' . $evaluationId);
}

$pageTitle = 'Edit Evaluation - ' . $evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name'];
$pageHeader = true;
$pageDescription = 'Edit job template-based performance evaluation';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'save_evaluation') {
            // Save all evaluation data at once
            $allSuccess = true;
            $errors = [];
            
            // Update KPI results
            if (!empty($evaluation['kpi_results'])) {
                foreach ($evaluation['kpi_results'] as $kpi) {
                    $kpiId = $kpi['kpi_id'];
                    $data = [
                        'achieved_value' => $_POST["kpi_achieved_$kpiId"] ?? null,
                        'score' => $_POST["kpi_score_$kpiId"] ?? null,
                        'comments' => $_POST["kpi_comments_$kpiId"] ?? ''
                    ];
                    
                    if (!empty($data['achieved_value']) || !empty($data['score']) || !empty($data['comments'])) {
                        $result = $evaluationClass->updateKPIResult($evaluationId, $kpiId, $data);
                        if (!$result) {
                            $allSuccess = false;
                            $errors[] = "Failed to update KPI: " . $kpi['kpi_name'];
                        }
                    }
                }
            }
            
            // Update Competency results
            if (!empty($evaluation['competency_results'])) {
                foreach ($evaluation['competency_results'] as $competency) {
                    $competencyId = $competency['competency_id'];
                    $data = [
                        'achieved_level' => $_POST["competency_level_$competencyId"] ?? null,
                        'score' => $_POST["competency_score_$competencyId"] ?? null,
                        'comments' => $_POST["competency_comments_$competencyId"] ?? ''
                    ];
                    
                    if (!empty($data['achieved_level']) || !empty($data['score']) || !empty($data['comments'])) {
                        $result = $evaluationClass->updateCompetencyResult($evaluationId, $competencyId, $data);
                        if (!$result) {
                            $allSuccess = false;
                            $errors[] = "Failed to update Competency: " . $competency['competency_name'];
                        }
                    }
                }
            }
            
            // Update Responsibility results
            if (!empty($evaluation['responsibility_results'])) {
                foreach ($evaluation['responsibility_results'] as $responsibility) {
                    $responsibilityId = $responsibility['responsibility_id'];
                    $data = [
                        'score' => $_POST["responsibility_score_$responsibilityId"] ?? null,
                        'comments' => $_POST["responsibility_comments_$responsibilityId"] ?? ''
                    ];
                    
                    if (!empty($data['score']) || !empty($data['comments'])) {
                        $result = $evaluationClass->updateResponsibilityResult($evaluationId, $responsibilityId, $data);
                        if (!$result) {
                            $allSuccess = false;
                            $errors[] = "Failed to update Responsibility";
                        }
                    }
                }
            }
            
            // Update Value results
            if (!empty($evaluation['value_results'])) {
                foreach ($evaluation['value_results'] as $value) {
                    $valueId = $value['value_id'];
                    $data = [
                        'score' => $_POST["value_score_$valueId"] ?? null,
                        'comments' => $_POST["value_comments_$valueId"] ?? ''
                    ];
                    
                    if (!empty($data['score']) || !empty($data['comments'])) {
                        $result = $evaluationClass->updateValueResult($evaluationId, $valueId, $data);
                        if (!$result) {
                            $allSuccess = false;
                            $errors[] = "Failed to update Value: " . $value['value_name'];
                        }
                    }
                }
            }
            
            // Update overall evaluation data
            $updateData = [
                'overall_comments' => $_POST['overall_comments'] ?? '',
                'goals_next_period' => $_POST['goals_next_period'] ?? '',
                'development_areas' => $_POST['development_areas'] ?? '',
                'strengths' => $_POST['strengths'] ?? ''
            ];
            
            if ($_POST['submit_type'] === 'submit') {
                $updateData['status'] = 'submitted';
            }
            
            $result = $evaluationClass->updateEvaluation($evaluationId, $updateData);
            
            if ($result && $allSuccess) {
                $message = $_POST['submit_type'] === 'submit' ? 'Evaluation submitted successfully!' : 'Evaluation saved successfully!';
                setFlashMessage($message, 'success');
                redirect('/evaluation/view.php?id=' . $evaluationId);
            } else {
                if (!empty($errors)) {
                    setFlashMessage('Some sections failed to save: ' . implode(', ', $errors), 'error');
                } else {
                    setFlashMessage('Failed to update evaluation. Please try again.', 'error');
                }
            }
        }
    } catch (Exception $e) {
        error_log('Evaluation update error: ' . $e->getMessage());
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Edit Performance Evaluation</h5>
                        <div class="text-muted">
                            <strong>Employee:</strong> <?php echo htmlspecialchars($evaluation['employee_first_name'] . ' ' . $evaluation['employee_last_name']); ?> |
                            <strong>Position:</strong> <?php echo htmlspecialchars($evaluation['job_template_title'] ?? 'N/A'); ?> |
                            <strong>Period:</strong> <?php echo htmlspecialchars($evaluation['period_name']); ?> |
                            <strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($evaluation['status']); ?></span>
                        </div>
                    </div>
                    <div>
                        <a href="/evaluation/view.php?id=<?php echo $evaluationId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-eye me-2"></i>View Only
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Progress Indicator -->
                <div class="progress mb-4" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="evaluation-progress"></div>
                </div>
                
                <!-- Overall Evaluation Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="save_evaluation">
                
                <!-- KPIs Section -->
                <?php if (!empty($evaluation['kpi_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Key Performance Indicators (KPIs)
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['kpis']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['kpi_results'] as $kpi): ?>
                    <div class="kpi-item mb-3 p-3 border rounded">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($kpi['kpi_name']); ?></h6>
                                <p class="text-muted mb-2">
                                    <strong>Category:</strong> <?php echo htmlspecialchars($kpi['category']); ?> |
                                    <strong>Target:</strong> <?php echo number_format($kpi['target_value'], 2); ?> <?php echo htmlspecialchars($kpi['measurement_unit']); ?>
                                </p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Achieved Value</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control"
                                               name="kpi_achieved_<?php echo $kpi['kpi_id']; ?>"
                                               value="<?php echo $kpi['achieved_value'] ?? ''; ?>"
                                               placeholder="Enter achieved value">
                                        <span class="input-group-text"><?php echo htmlspecialchars($kpi['measurement_unit']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Comments</label>
                                    <textarea class="form-control" rows="2"
                                              name="kpi_comments_<?php echo $kpi['kpi_id']; ?>"
                                              placeholder="Provide feedback on KPI performance..."><?php echo htmlspecialchars($kpi['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <select class="form-select" name="kpi_score_<?php echo $kpi['kpi_id']; ?>">
                                        <option value="">Select score...</option>
                                        <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($kpi['score'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> - <?php echo getScoreLabel($i); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Competencies Section -->
                <?php if (!empty($evaluation['competency_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Skills, Knowledge, and Competencies
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['competencies']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['competency_results'] as $competency): ?>
                    <div class="competency-item mb-3 p-3 border rounded">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($competency['competency_name']); ?></h6>
                                <p class="text-muted mb-2">
                                    <strong>Category:</strong> <?php echo htmlspecialchars($competency['category_name'] ?? 'N/A'); ?> |
                                    <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $competency['competency_type'])); ?> |
                                    <strong>Required Level:</strong> <?php echo ucfirst($competency['required_level']); ?>
                                </p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Achieved Level</label>
                                    <select class="form-select" name="competency_level_<?php echo $competency['competency_id']; ?>">
                                        <option value="">Select achieved level...</option>
                                        <option value="basic" <?php echo ($competency['achieved_level'] === 'basic') ? 'selected' : ''; ?>>Basic</option>
                                        <option value="intermediate" <?php echo ($competency['achieved_level'] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo ($competency['achieved_level'] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                        <option value="expert" <?php echo ($competency['achieved_level'] === 'expert') ? 'selected' : ''; ?>>Expert</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Comments</label>
                                    <textarea class="form-control" rows="2"
                                              name="competency_comments_<?php echo $competency['competency_id']; ?>"
                                              placeholder="Provide feedback on competency demonstration..."><?php echo htmlspecialchars($competency['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <select class="form-select" name="competency_score_<?php echo $competency['competency_id']; ?>">
                                        <option value="">Select score...</option>
                                        <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($competency['score'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> - <?php echo getScoreLabel($i); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Responsibilities Section -->
                <?php if (!empty($evaluation['responsibility_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Key Responsibilities
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['responsibilities']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['responsibility_results'] as $responsibility): ?>
                    <div class="responsibility-item mb-3 p-3 border rounded">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-2">Responsibility #<?php echo $responsibility['sort_order']; ?></h6>
                                <p class="mb-3"><?php echo htmlspecialchars($responsibility['responsibility_text']); ?></p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Comments</label>
                                    <textarea class="form-control" rows="2"
                                              name="responsibility_comments_<?php echo $responsibility['responsibility_id']; ?>"
                                              placeholder="Provide feedback on responsibility fulfillment..."><?php echo htmlspecialchars($responsibility['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <select class="form-select" name="responsibility_score_<?php echo $responsibility['responsibility_id']; ?>">
                                        <option value="">Select score...</option>
                                        <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($responsibility['score'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> - <?php echo getScoreLabel($i); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Values Section -->
                <?php if (!empty($evaluation['value_results'])): ?>
                <div class="evaluation-section mb-4">
                    <h6 class="section-title border-bottom pb-2 mb-3">
                        Living Our Values
                        <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['values']; ?>%</span>
                    </h6>
                    
                    <?php foreach ($evaluation['value_results'] as $value): ?>
                    <div class="value-item mb-3 p-3 border rounded">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($value['value_name']); ?></h6>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($value['description']); ?></p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Comments</label>
                                    <textarea class="form-control" rows="2"
                                              name="value_comments_<?php echo $value['value_id']; ?>"
                                              placeholder="Provide feedback on how this value is demonstrated..."><?php echo htmlspecialchars($value['comments'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (1-5)</label>
                                    <select class="form-select" name="value_score_<?php echo $value['value_id']; ?>">
                                        <option value="">Select score...</option>
                                        <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($value['score'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> - <?php echo getScoreLabel($i); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php
                // Check if this evaluation has job template data
                $hasJobTemplateData = !empty($evaluation['kpi_results']) ||
                                     !empty($evaluation['competency_results']) ||
                                     !empty($evaluation['responsibility_results']) ||
                                     !empty($evaluation['value_results']);
                
                // LOG: Debug whether we should show old or new format
                error_log("EVALUATION FORMAT CHECK - Has Job Template Data: " . ($hasJobTemplateData ? 'YES' : 'NO'));
                error_log("EVALUATION FORMAT CHECK - Job Template ID: " . ($evaluation['job_template_id'] ?? 'NULL'));
                ?>
                
                <?php if (!$hasJobTemplateData): ?>
                <!-- Legacy Evaluation Warning -->
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Legacy Evaluation Format
                    </h6>
                    <p class="mb-0">This evaluation was created using the legacy format and does not contain job template dimensions. Please contact HR to migrate this evaluation to the new job template-based system.</p>
                </div>
                <?php endif; ?>
                
                    <!-- Overall Comments -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">Overall Evaluation</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Overall Rating</label>
                                    <div class="form-control-plaintext">
                                        <span class="h4 text-primary" id="overall-rating"><?php echo number_format($evaluation['overall_rating'] ?? 0, 2); ?></span>
                                        <small class="text-muted">/ 5.00</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Completion Status</label>
                                    <div class="form-control-plaintext">
                                        <span class="badge bg-info" id="completion-status">0% Complete</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Overall Performance Summary</label>
                            <textarea class="form-control" name="overall_comments" rows="4" 
                                      placeholder="Provide an overall summary of performance..."><?php echo htmlspecialchars($evaluation['overall_comments'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Development and Goals -->
                    <div class="evaluation-section mb-4">
                        <h6 class="section-title border-bottom pb-2 mb-3">Development and Goals</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Strengths</label>
                                    <textarea class="form-control" name="strengths" rows="4" 
                                              placeholder="List key strengths and achievements..."><?php echo htmlspecialchars($evaluation['strengths'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Development Areas</label>
                                    <textarea class="form-control" name="development_areas" rows="4" 
                                              placeholder="Identify areas for improvement..."><?php echo htmlspecialchars($evaluation['development_areas'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Goals for Next Period</label>
                            <textarea class="form-control" name="goals_next_period" rows="4" 
                                      placeholder="Set goals and objectives for the next evaluation period..."><?php echo htmlspecialchars($evaluation['goals_next_period'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="/evaluation/list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <div>
                            <button type="submit" name="submit_type" value="save" class="btn btn-outline-primary me-2">
                                <i class="fas fa-save me-2"></i>Save Draft
                            </button>
                            <button type="submit" name="submit_type" value="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Submit Evaluation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.evaluation-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-title {
    color: #495057;
    font-weight: 600;
}

.kpi-item, .competency-item, .responsibility-item, .value-item {
    background: white;
    transition: box-shadow 0.2s;
}

.kpi-item:hover, .competency-item:hover, .responsibility-item:hover, .value-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.save-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.3s;
}

.save-indicator.show {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateProgress() {
        // Calculate completion percentage based on filled score fields
        const totalItems = document.querySelectorAll('select[name*="_score_"]').length;
        const completedItems = document.querySelectorAll('select[name*="_score_"]:not([value=""])').length;
        
        const percentage = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
        
        // Update progress bar
        const progressBar = document.getElementById('evaluation-progress');
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
        }
        
        // Update completion status
        const completionStatus = document.getElementById('completion-status');
        if (completionStatus) {
            completionStatus.textContent = percentage + '% Complete';
            
            if (percentage === 100) {
                completionStatus.classList.remove('bg-info');
                completionStatus.classList.add('bg-success');
            } else {
                completionStatus.classList.remove('bg-success');
                completionStatus.classList.add('bg-info');
            }
        }
        
        // Update overall rating display (simplified client-side calculation)
        updateOverallRating();
    }
    
    function updateOverallRating() {
        const scores = [];
        
        document.querySelectorAll('select[name*="_score_"]').forEach(select => {
            if (select.value) {
                scores.push(parseFloat(select.value));
            }
        });
        
        const overallRatingElement = document.getElementById('overall-rating');
        if (overallRatingElement && scores.length > 0) {
            const average = scores.reduce((a, b) => a + b, 0) / scores.length;
            overallRatingElement.textContent = average.toFixed(2);
        }
    }
    
    // Initialize progress on page load
    updateProgress();
    
    // Update progress when scores change
    document.querySelectorAll('select[name*="_score_"]').forEach(select => {
        select.addEventListener('change', updateProgress);
    });
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>