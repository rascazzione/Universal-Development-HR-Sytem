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

// Get evaluation details with evidence-based data
$evaluation = $evaluationClass->getEvidenceEvaluation($evaluationId);
if (!$evaluation) {
    setFlashMessage('Evaluation not found.', 'error');
    redirect('/evaluation/list.php');
}

// Check if evidence data is available for this evaluation
$hasEvidenceData = false;
if (!empty($evaluation['kpi_results'])) {
    foreach ($evaluation['kpi_results'] as $kpi) {
        if (strpos($kpi['kpi_id'], 'evidence_') === 0) {
            $hasEvidenceData = true;
            break;
        }
    }
}
if (!$hasEvidenceData && !empty($evaluation['competency_results'])) {
    foreach ($evaluation['competency_results'] as $competency) {
        if (strpos($competency['competency_id'], 'evidence_') === 0) {
            $hasEvidenceData = true;
            break;
        }
    }
}
if (!$hasEvidenceData && !empty($evaluation['responsibility_results'])) {
    foreach ($evaluation['responsibility_results'] as $responsibility) {
        if (strpos($responsibility['responsibility_id'], 'evidence_') === 0) {
            $hasEvidenceData = true;
            break;
        }
    }
}
if (!$hasEvidenceData && !empty($evaluation['value_results'])) {
    foreach ($evaluation['value_results'] as $value) {
        if (strpos($value['value_id'], 'evidence_') === 0) {
            $hasEvidenceData = true;
            break;
        }
    }
}

// LOG: Debug evaluation structure and job template data
error_log("EVALUATION DEBUG - ID: $evaluationId");
error_log("EVALUATION DEBUG - Job Template ID: " . ($evaluation['job_template_id'] ?? 'NULL'));
error_log("EVALUATION DEBUG - Has Evidence Data: " . ($hasEvidenceData ? 'YES' : 'NO'));
error_log("EVALUATION DEBUG - KPI Results Count: " . count($evaluation['kpi_results'] ?? []));
error_log("EVALUATION DEBUG - Competency Results Count: " . count($evaluation['competency_results'] ?? []));
error_log("EVALUATION DEBUG - Responsibility Results Count: " . count($evaluation['responsibility_results'] ?? []));
error_log("EVALUATION DEBUG - Value Results Count: " . count($evaluation['value_results'] ?? []));

// Check permissions using the enhanced authorization function
if (!canEditEvaluation($evaluation)) {
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
            
            // Handle workflow state transitions
            if ($_POST['submit_type'] === 'submit') {
                if ($evaluation['status'] !== 'draft') {
                    setFlashMessage('Cannot submit evaluation. Current status: ' . $evaluation['status'], 'error');
                    redirect('/evaluation/view.php?id=' . $evaluationId);
                }
                $updateData['status'] = 'submitted';
            } elseif ($_POST['submit_type'] === 'approve') {
                if ($evaluation['status'] !== 'submitted' || $_SESSION['user_role'] !== 'hr_admin') {
                    setFlashMessage('Cannot approve evaluation. Invalid status or permissions.', 'error');
                    redirect('/evaluation/view.php?id=' . $evaluationId);
                }
                $updateData['status'] = 'approved';
            } elseif ($_POST['submit_type'] === 'reject') {
                if ($evaluation['status'] !== 'submitted' || $_SESSION['user_role'] !== 'hr_admin') {
                    setFlashMessage('Cannot reject evaluation. Invalid status or permissions.', 'error');
                    redirect('/evaluation/view.php?id=' . $evaluationId);
                }
                $updateData['status'] = 'rejected';
            }
            
            $result = $evaluationClass->updateEvaluation($evaluationId, $updateData);
            
            if ($result && $allSuccess) {
                $message = match($_POST['submit_type']) {
                    'submit' => 'Evaluation submitted successfully!',
                    'approve' => 'Evaluation approved successfully!',
                    'reject' => 'Evaluation rejected successfully!',
                    default => 'Evaluation saved successfully!'
                };
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
                
                <!-- Section Controls -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="expandAll">
                            <i class="fas fa-expand-alt me-1"></i>Expand All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll">
                            <i class="fas fa-compress-alt me-1"></i>Collapse All
                        </button>
                    </div>
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>Click section headers to expand/collapse
                    </div>
                </div>
                
                <!-- Overall Evaluation Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="save_evaluation">
                
                <!-- Accordion Container -->
                <div class="accordion" id="evaluationAccordion">
                
                <!-- KPIs Section -->
                <?php if (!empty($evaluation['kpi_results'])): ?>
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#kpisSection">
                            <i class="fas fa-chart-bar me-2"></i>
                            Key Performance Indicators (KPIs)
                            <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['kpis']; ?>%</span>
                            <?php if ($hasEvidenceData): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-chart-line me-1"></i>Evidence-Informed
                            </span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($evaluation['kpi_results']); ?> items</span>
                        </button>
                    </h2>
                    <div id="kpisSection" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">KPI Name</th>
                                            <th style="width: 15%;">Category</th>
                                            <th style="width: 12%;">Target</th>
                                            <th style="width: 12%;">Achieved</th>
                                            <th style="width: 10%;">Score</th>
                                            <th style="width: 26%;">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluation['kpi_results'] as $kpi): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($kpi['kpi_name']); ?></strong>
                                                <?php if ($hasEvidenceData && strpos($kpi['kpi_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-info badge-sm ms-1" title="Evidence-based">
                                                    <i class="fas fa-lightbulb"></i>
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($kpi['category']); ?></small></td>
                                            <td><small><?php echo number_format($kpi['target_value'], 2); ?> <?php echo htmlspecialchars($kpi['measurement_unit']); ?></small></td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($kpi['kpi_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success"><?php echo $kpi['achieved_value']; ?></span>
                                                <?php else: ?>
                                                <input type="number" step="0.01" class="form-control form-control-sm" 
                                                       name="kpi_achieved_<?php echo $kpi['kpi_id']; ?>"
                                                       value="<?php echo $kpi['achieved_value'] ?? ''; ?>"
                                                       placeholder="Value">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($kpi['kpi_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success fs-6"><?php echo $kpi['score']; ?></span>
                                                <?php else: ?>
                                                <select class="form-select form-select-sm" name="kpi_score_<?php echo $kpi['kpi_id']; ?>">
                                                    <option value="">-</option>
                                                    <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($kpi['score'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" rows="1"
                                                          name="kpi_comments_<?php echo $kpi['kpi_id']; ?>"
                                                          placeholder="Comments..."><?php echo htmlspecialchars($kpi['comments'] ?? ''); ?></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Competencies Section -->
                <?php if (!empty($evaluation['competency_results'])): ?>
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#competenciesSection">
                            <i class="fas fa-brain me-2"></i>
                            Skills, Knowledge, and Competencies
                            <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['competencies']; ?>%</span>
                            <?php if ($hasEvidenceData): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-chart-line me-1"></i>Evidence-Informed
                            </span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($evaluation['competency_results']); ?> items</span>
                        </button>
                    </h2>
                    <div id="competenciesSection" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Competency</th>
                                            <th style="width: 15%;">Category</th>
                                            <th style="width: 12%;">Required Level</th>
                                            <th style="width: 12%;">Achieved Level</th>
                                            <th style="width: 10%;">Score</th>
                                            <th style="width: 26%;">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluation['competency_results'] as $competency): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($competency['competency_name']); ?></strong>
                                                <?php if ($hasEvidenceData && strpos($competency['competency_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-info badge-sm ms-1" title="Evidence-based">
                                                    <i class="fas fa-lightbulb"></i>
                                                </span>
                                                <?php endif; ?>
                                                <br><small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $competency['competency_type'])); ?></small>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($competency['category_name'] ?? 'N/A'); ?></small></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($competency['required_level']); ?></span></td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($competency['competency_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success"><?php echo ucfirst($competency['achieved_level']); ?></span>
                                                <?php else: ?>
                                                <select class="form-select form-select-sm" name="competency_level_<?php echo $competency['competency_id']; ?>">
                                                    <option value="">-</option>
                                                    <option value="basic" <?php echo ($competency['achieved_level'] === 'basic') ? 'selected' : ''; ?>>Basic</option>
                                                    <option value="intermediate" <?php echo ($competency['achieved_level'] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="advanced" <?php echo ($competency['achieved_level'] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                    <option value="expert" <?php echo ($competency['achieved_level'] === 'expert') ? 'selected' : ''; ?>>Expert</option>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($competency['competency_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success fs-6"><?php echo $competency['score']; ?></span>
                                                <?php else: ?>
                                                <select class="form-select form-select-sm" name="competency_score_<?php echo $competency['competency_id']; ?>">
                                                    <option value="">-</option>
                                                    <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($competency['score'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" rows="1"
                                                          name="competency_comments_<?php echo $competency['competency_id']; ?>"
                                                          placeholder="Comments..."><?php echo htmlspecialchars($competency['comments'] ?? ''); ?></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Responsibilities Section -->
                <?php if (!empty($evaluation['responsibility_results'])): ?>
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#responsibilitiesSection">
                            <i class="fas fa-tasks me-2"></i>
                            Key Responsibilities
                            <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['responsibilities']; ?>%</span>
                            <?php if ($hasEvidenceData): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-chart-line me-1"></i>Evidence-Informed
                            </span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($evaluation['responsibility_results']); ?> items</span>
                        </button>
                    </h2>
                    <div id="responsibilitiesSection" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60%;">Responsibility</th>
                                            <th style="width: 15%;">Score</th>
                                            <th style="width: 25%;">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluation['responsibility_results'] as $responsibility): ?>
                                        <tr>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($responsibility['responsibility_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-info badge-sm me-1" title="Evidence-based">
                                                    <i class="fas fa-lightbulb"></i>
                                                </span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($responsibility['responsibility_text']); ?>
                                            </td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($responsibility['responsibility_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success fs-6"><?php echo $responsibility['score']; ?></span>
                                                <?php else: ?>
                                                <select class="form-select form-select-sm" name="responsibility_score_<?php echo $responsibility['responsibility_id']; ?>">
                                                    <option value="">-</option>
                                                    <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($responsibility['score'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" rows="1"
                                                          name="responsibility_comments_<?php echo $responsibility['responsibility_id']; ?>"
                                                          placeholder="Comments..."><?php echo htmlspecialchars($responsibility['comments'] ?? ''); ?></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Values Section -->
                <?php if (!empty($evaluation['value_results'])): ?>
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#valuesSection">
                            <i class="fas fa-heart me-2"></i>
                            Living Our Values
                            <span class="badge bg-primary ms-2"><?php echo $evaluation['section_weights']['values']; ?>%</span>
                            <?php if ($hasEvidenceData): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-chart-line me-1"></i>Evidence-Informed
                            </span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($evaluation['value_results']); ?> items</span>
                        </button>
                    </h2>
                    <div id="valuesSection" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Value</th>
                                            <th style="width: 35%;">Description</th>
                                            <th style="width: 15%;">Score</th>
                                            <th style="width: 25%;">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluation['value_results'] as $value): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($value['value_name']); ?></strong>
                                                <?php if ($hasEvidenceData && strpos($value['value_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-info badge-sm ms-1" title="Evidence-based">
                                                    <i class="fas fa-lightbulb"></i>
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($value['description']); ?></small></td>
                                            <td>
                                                <?php if ($hasEvidenceData && strpos($value['value_id'], 'evidence_') === 0): ?>
                                                <span class="badge bg-success fs-6"><?php echo $value['score']; ?></span>
                                                <?php else: ?>
                                                <select class="form-select form-select-sm" name="value_score_<?php echo $value['value_id']; ?>">
                                                    <option value="">-</option>
                                                    <?php for ($i = 1; $i <= 5; $i += 0.5): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($value['score'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" rows="1"
                                                          name="value_comments_<?php echo $value['value_id']; ?>"
                                                          placeholder="Comments..."><?php echo htmlspecialchars($value['comments'] ?? ''); ?></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                </div><!-- End Accordion Container -->
                
                <?php
                // Check if this evaluation has evidence-based data
                $hasEvidenceData = !empty($evaluation['evidence_results']);
                $hasJobTemplateData = !empty($evaluation['kpi_results']) ||
                                     !empty($evaluation['competency_results']) ||
                                     !empty($evaluation['responsibility_results']) ||
                                     !empty($evaluation['value_results']);
                
                // LOG: Debug whether we should show evidence or compatibility format
                error_log("EVALUATION FORMAT CHECK - Has Evidence Data: " . ($hasEvidenceData ? 'YES' : 'NO'));
                error_log("EVALUATION FORMAT CHECK - Has Job Template Data: " . ($hasJobTemplateData ? 'YES' : 'NO'));
                error_log("EVALUATION FORMAT CHECK - Evidence Results Count: " . count($evaluation['evidence_results'] ?? []));
                ?>
                
                <?php if ($hasEvidenceData): ?>
                <!-- Enhanced Evidence Summary Section -->
                <div class="evidence-summary-section mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Evidence-Based Evaluation Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <?php
                                $totalEntries = 0;
                                $overallAvgRating = 0;
                                $dimensionCount = 0;
                                foreach ($evaluation['evidence_results'] as $result) {
                                    $totalEntries += $result['evidence_count'];
                                    $overallAvgRating += $result['avg_star_rating'];
                                    $dimensionCount++;
                                }
                                $overallAvgRating = $dimensionCount > 0 ? $overallAvgRating / $dimensionCount : 0;
                                ?>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h3 text-primary mb-1"><?php echo $totalEntries; ?></div>
                                        <div class="text-muted small">Total Evidence Entries</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h3 text-success mb-1"><?php echo number_format($overallAvgRating, 1); ?>/5</div>
                                        <div class="text-muted small">Overall Rating</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h3 text-info mb-1"><?php echo count($evaluation['evidence_results']); ?></div>
                                        <div class="text-muted small">Dimensions Covered</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <?php
                                        $confidenceLevel = 'Low';
                                        $confidenceClass = 'text-warning';
                                        if ($totalEntries >= 20) {
                                            $confidenceLevel = 'High';
                                            $confidenceClass = 'text-success';
                                        } elseif ($totalEntries >= 10) {
                                            $confidenceLevel = 'Medium';
                                            $confidenceClass = 'text-info';
                                        }
                                        ?>
                                        <div class="h3 <?php echo $confidenceClass; ?> mb-1"><?php echo $confidenceLevel; ?></div>
                                        <div class="text-muted small">Evidence Confidence</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Evidence by Dimension -->
                            <h6 class="border-bottom pb-2 mb-3">Evidence by Dimension</h6>
                            <div class="row">
                                <?php foreach ($evaluation['evidence_results'] as $result): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="evidence-dimension-card p-3 border rounded">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?php echo ucfirst($result['dimension']); ?></h6>
                                            <span class="badge bg-secondary"><?php echo $result['evidence_count']; ?> entries</span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="small text-muted">Average Rating:</span>
                                                <div class="evidence-rating">
                                                    <?php
                                                    $rating = $result['avg_star_rating'];
                                                    for ($i = 1; $i <= 5; $i++):
                                                        if ($i <= floor($rating)): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php elseif ($i <= ceil($rating) && $rating > floor($rating)): ?>
                                                            <i class="fas fa-star-half-alt text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-muted"></i>
                                                        <?php endif;
                                                    endfor; ?>
                                                    <span class="ms-1 small"><?php echo number_format($rating, 1); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row text-center small">
                                            <div class="col-6">
                                                <div class="text-success">
                                                    <i class="fas fa-thumbs-up me-1"></i><?php echo $result['total_positive_entries']; ?>
                                                </div>
                                                <div class="text-muted">Positive</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i><?php echo $result['total_negative_entries']; ?>
                                                </div>
                                                <div class="text-muted">Improvement</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm w-100"
                                                    onclick="showEvidenceDetails('<?php echo $result['dimension']; ?>')">
                                                <i class="fas fa-eye me-1"></i>View Evidence Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (!$hasJobTemplateData): ?>
                <!-- Legacy Evaluation Warning -->
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>No Evidence Data Available
                    </h6>
                    <p class="mb-0">No evidence entries were found for this evaluation period. Consider collecting evidence through the Growth Evidence Journal system for more comprehensive evaluations.</p>
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
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <?php if ($evaluation['status'] === 'draft' && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'hr_admin')): ?>
                            <button type="submit" name="submit_type" value="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Submit Evaluation
                            </button>
                            <?php elseif ($evaluation['status'] === 'submitted' && $_SESSION['user_role'] === 'hr_admin'): ?>
                            <button type="submit" name="submit_type" value="approve" class="btn btn-success me-2">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                            <button type="submit" name="submit_type" value="reject" class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Reject
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Accordion Styles */
.accordion-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}

.accordion-button {
    font-weight: 600;
    background-color: #f8f9fa;
    padding: 1rem 1.25rem;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: #dee2e6;
}

.accordion-body {
    padding: 1.5rem;
}

/* Compact Table Styles */
.compact-table {
    margin-bottom: 0;
    font-size: 0.9rem;
}

.compact-table thead th {
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.75rem 0.5rem;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.compact-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

.compact-table tbody tr:hover {
    background-color: #f8f9fa;
}

.compact-table .form-control-sm,
.compact-table .form-select-sm {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}

.compact-table textarea.form-control-sm {
    resize: vertical;
    min-height: 2.5rem;
}

.compact-table .badge-sm {
    font-size: 0.7rem;
    padding: 0.2em 0.4em;
}

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

/* Enhanced Evidence Styles */
.evidence-summary-section .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.evidence-dimension-card {
    background: white;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.evidence-dimension-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.evidence-rating .fas, .evidence-rating .far {
    font-size: 0.9em;
}

.evidence-insight {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.evidence-score-display {
    border: 2px solid #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
}

.evidence-confidence .badge {
    font-size: 0.75em;
    padding: 0.25em 0.5em;
}

.evidence-suggestion .badge {
    font-size: 0.8em;
}

/* Evidence Modal Styles */
.evidence-modal .modal-dialog {
    max-width: 800px;
}

.evidence-entry {
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    transition: background-color 0.2s;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 0.375rem;
}

.evidence-entry:hover {
    background: #e9ecef;
}

.evidence-entry.positive {
    border-left-color: #28a745;
}

.evidence-entry.negative {
    border-left-color: #dc3545;
}

.evidence-entry.neutral {
    border-left-color: #ffc107;
}

/* Responsive Design */
@media (max-width: 768px) {
    .evidence-summary-section .row > div {
        margin-bottom: 1rem;
    }
    
    .evidence-dimension-card {
        margin-bottom: 1rem;
    }
    
    .kpi-item .row,
    .competency-item .row,
    .responsibility-item .row,
    .value-item .row {
        flex-direction: column;
    }
    
    .evidence-score-display {
        margin-top: 1rem;
    }
    
    .evidence-insight {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .evaluation-section {
        padding: 15px;
    }
    
    .evidence-summary-section .col-md-3 {
        text-align: left !important;
        margin-bottom: 1rem;
    }
    
    .evidence-dimension-card .row {
        text-align: center;
    }
    
    .card-header h6 {
        font-size: 1rem;
    }
}

/* Loading States */
.evidence-loading {
    display: none;
    text-align: center;
    padding: 2rem;
}

.evidence-loading.show {
    display: block;
}

/* Animation for evidence insights */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.evidence-insight {
    animation: fadeInUp 0.5s ease-out;
}
</style>

<!-- Evidence Details Modal -->
<div class="modal fade evidence-modal" id="evidenceModal" tabindex="-1" aria-labelledby="evidenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evidenceModalLabel">
                    <i class="fas fa-chart-line me-2"></i>Evidence Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="evidence-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading evidence details...</p>
                </div>
                <div id="evidenceContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Expand/Collapse All functionality
    const expandAllBtn = document.getElementById('expandAll');
    const collapseAllBtn = document.getElementById('collapseAll');
    
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.accordion-collapse');
            collapses.forEach(collapse => {
                const bsCollapse = new bootstrap.Collapse(collapse, {
                    show: true
                });
            });
        });
    }
    
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.accordion-collapse.show');
            collapses.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        });
    }
    
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

// Evidence drill-down functionality
function showEvidenceDetails(dimension) {
    const modal = new bootstrap.Modal(document.getElementById('evidenceModal'));
    const modalTitle = document.getElementById('evidenceModalLabel');
    const evidenceContent = document.getElementById('evidenceContent');
    const loadingDiv = document.querySelector('.evidence-loading');
    
    // Update modal title
    modalTitle.innerHTML = `<i class="fas fa-chart-line me-2"></i>Evidence Details - ${dimension.charAt(0).toUpperCase() + dimension.slice(1)}`;
    
    // Show loading state
    loadingDiv.classList.add('show');
    evidenceContent.innerHTML = '';
    
    // Show modal
    modal.show();
    
    // Get evaluation ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const evaluationId = urlParams.get('id');
    
    // Fetch evidence details from API
    fetch(`/api/evidence-details.php?evaluation_id=${evaluationId}&dimension=${dimension}`)
        .then(response => response.json())
        .then(data => {
            loadingDiv.classList.remove('show');
            if (data.success) {
                evidenceContent.innerHTML = generateEvidenceDetailsHTML(data);
            } else {
                evidenceContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading evidence details: ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            loadingDiv.classList.remove('show');
            evidenceContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading evidence details. Please try again.
                </div>
            `;
            console.error('Error fetching evidence details:', error);
        });
}

function generateEvidenceDetailsHTML(data) {
    const { dimension, entries, summary } = data;
    
    let html = `
        <div class="mb-3">
            <h6>Evidence Entries for ${dimension.charAt(0).toUpperCase() + dimension.slice(1)}</h6>
            <p class="text-muted">Showing ${summary.total_entries} evidence entries from the evaluation period.</p>
        </div>
    `;
    
    if (entries.length === 0) {
        html += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No evidence entries found for this dimension in the evaluation period.
            </div>
        `;
        return html;
    }
    
    entries.forEach(entry => {
        const entryClass = entry.star_rating >= 4 ? 'positive' : entry.star_rating <= 2 ? 'negative' : 'neutral';
        const stars = '★'.repeat(entry.star_rating) + '☆'.repeat(5 - entry.star_rating);
        const entryDate = new Date(entry.entry_date).toLocaleDateString();
        const managerName = entry.manager_first_name && entry.manager_last_name ?
            `${entry.manager_first_name} ${entry.manager_last_name}` : 'Unknown Manager';
        
        html += `
            <div class="evidence-entry ${entryClass}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="evidence-rating text-warning">${stars}</div>
                    <small class="text-muted">${entryDate}</small>
                </div>
                <p class="mb-2">${entry.content}</p>
                <small class="text-muted">
                    <i class="fas fa-user me-1"></i>Recorded by: ${managerName}
                </small>
            </div>
        `;
    });
    
    html += `
        <div class="mt-3 p-3 bg-light rounded">
            <h6 class="mb-2">Summary</h6>
            <div class="row text-center">
                <div class="col-4">
                    <div class="h5 text-success mb-1">${summary.positive_entries}</div>
                    <div class="small text-muted">Positive (4-5★)</div>
                </div>
                <div class="col-4">
                    <div class="h5 text-warning mb-1">${summary.neutral_entries}</div>
                    <div class="small text-muted">Neutral (3★)</div>
                </div>
                <div class="col-4">
                    <div class="h5 text-danger mb-1">${summary.negative_entries}</div>
                    <div class="small text-muted">Needs Improvement (1-2★)</div>
                </div>
            </div>
            <div class="text-center mt-2">
                <strong>Average Rating: ${summary.average_rating.toFixed(1)}/5</strong>
            </div>
        </div>
    `;
    
    return html;
}
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
