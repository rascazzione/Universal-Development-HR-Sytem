<?php
/**
 * Dashboard Job Template Component
 * Renders the job template in a collapsible accordion format
 */

/**
 * Render job template as a collapsible accordion for dashboard
 * 
 * @param array $jobTemplateData - Complete job template data with all sections
 * @param int $employeeId - Employee ID for the autofeedback button
 * @param bool $isExpanded - Initial state of accordion (default: false)
 * @return string - HTML rendered content
 */
function renderDashboardJobTemplate($jobTemplateData, $employeeId, $isExpanded = false) {
    if (!$jobTemplateData || !$jobTemplateData['has_template']) {
        return renderNoTemplateMessage();
    }
    
    $template = $jobTemplateData['template'];
    $counts = $jobTemplateData['counts'];
    $details = $jobTemplateData['details'];
    
    // Calculate total sections
    $totalSections = 0;
    if ($counts['kpis'] > 0) $totalSections++;
    if ($counts['responsibilities'] > 0) $totalSections++;
    if (($counts['technical_skills'] > 0) || ($counts['soft_skills'] > 0)) $totalSections++;
    if ($counts['values'] > 0) $totalSections++;
    
    ob_start();
    ?>
    <div class="card job-template-accordion mb-4" data-employee-id="<?php echo $employeeId; ?>">
        <div class="card-header" id="jobTemplateHeader">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <button class="btn btn-link text-decoration-none w-100 text-start" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#jobTemplateCollapse" 
                            aria-expanded="<?php echo $isExpanded ? 'true' : 'false'; ?>"
                            aria-controls="jobTemplateCollapse">
                        <i class="fas fa-briefcase me-2"></i>
                        📋 Mi Ficha de Puesto - <?php echo htmlspecialchars($template['position_title']); ?>
                    </button>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark"><?php echo $totalSections; ?> Secciones</span>
                    <button class="btn btn-sm btn-success btn-self-feedback" onclick="goToSelfFeedback()">
                        <i class="fas fa-star me-1"></i> Dar Autofeedback
                    </button>
                </div>
            </div>
        </div>
        
        <div id="jobTemplateCollapse" class="collapse <?php echo $isExpanded ? 'show' : ''; ?>" 
             aria-labelledby="jobTemplateHeader" data-bs-parent="#dashboardAccordion">
            <div class="card-body">
                <?php if (!empty($template['description'])): ?>
                <div class="alert alert-info mb-4">
                    <small class="d-block mb-1"><strong>Descripción del puesto:</strong></small>
                    <?php echo htmlspecialchars($template['description']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($template['department'])): ?>
                <div class="mb-3">
                    <span class="badge bg-secondary">
                        <i class="fas fa-building me-1"></i>
                        <?php echo htmlspecialchars($template['department']); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <!-- Sección 1: KPIs -->
                <?php if (!empty($details['kpis']) && count($details['kpis']) > 0): ?>
                <div class="template-section">
                    <h6><i class="fas fa-chart-line me-2"></i>📊 Desired Results (KPIs)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>KPI</th>
                                    <th>Descripción</th>
                                    <th>Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details['kpis'] as $kpi): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($kpi['title'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($kpi['description'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($kpi['target'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sección 2: Responsabilidades -->
                <?php if (!empty($details['responsibilities']) && count($details['responsibilities']) > 0): ?>
                <div class="template-section">
                    <h6><i class="fas fa-tasks me-2"></i>📝 Key Responsibilities</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($details['responsibilities'] as $responsibility): ?>
                        <li class="list-group-item d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($responsibility['title'] ?? ''); ?></strong>
                                <?php if (!empty($responsibility['description'])): ?>
                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($responsibility['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Sección 3: Skills (Technical + Soft) -->
                <?php if ((!empty($details['technical_skills']) && count($details['technical_skills']) > 0) || 
                         (!empty($details['soft_skills']) && count($details['soft_skills']) > 0)): ?>
                <div class="template-section">
                    <h6><i class="fas fa-puzzle-piece me-2"></i>🧩 Skills & Competencies</h6>
                    
                    <!-- Tabs for Technical/Soft Skills -->
                    <ul class="nav nav-tabs mb-3" id="skillsTabs" role="tablist">
                        <?php if (!empty($details['technical_skills']) && count($details['technical_skills']) > 0): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="technical-tab" data-bs-toggle="tab" 
                                    data-bs-target="#technical-skills" type="button" role="tab">
                                <i class="fas fa-code me-1"></i>Technical Skills
                                <span class="badge bg-secondary ms-1"><?php echo count($details['technical_skills']); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($details['soft_skills']) && count($details['soft_skills']) > 0): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo empty($details['technical_skills']) ? 'active' : ''; ?>" 
                                    id="soft-tab" data-bs-toggle="tab" 
                                    data-bs-target="#soft-skills" type="button" role="tab">
                                <i class="fas fa-users me-1"></i>Soft Skills
                                <span class="badge bg-secondary ms-1"><?php echo count($details['soft_skills']); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="tab-content" id="skillsTabsContent">
                        <?php if (!empty($details['technical_skills']) && count($details['technical_skills']) > 0): ?>
                        <div class="tab-pane fade show active" id="technical-skills" role="tabpanel">
                            <div class="skills-grid">
                                <?php foreach ($details['technical_skills'] as $skill): ?>
                                <div class="skill-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($skill['skill_name'] ?? ''); ?></h6>
                                        <?php if (!empty($skill['level'])): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($skill['level']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($skill['description'])): ?>
                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($skill['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($details['soft_skills']) && count($details['soft_skills']) > 0): ?>
                        <div class="tab-pane fade <?php echo empty($details['technical_skills']) ? 'show active' : ''; ?>" 
                             id="soft-skills" role="tabpanel">
                            <div class="skills-grid">
                                <?php foreach ($details['soft_skills'] as $skill): ?>
                                <div class="skill-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($skill['skill_name'] ?? ''); ?></h6>
                                        <?php if (!empty($skill['level'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($skill['level']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($skill['description'])): ?>
                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($skill['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sección 4: Company Values -->
                <?php if (!empty($details['values']) && count($details['values']) > 0): ?>
                <div class="template-section">
                    <h6><i class="fas fa-gem me-2"></i>💎 Company Values</h6>
                    <div class="row">
                        <?php foreach ($details['values'] as $value): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 border-light">
                                <div class="card-body text-center">
                                    <div class="value-icon mb-2">
                                        <i class="fas fa-heart fa-2x text-danger"></i>
                                    </div>
                                    <h6 class="card-title"><?php echo htmlspecialchars($value['value_name'] ?? ''); ?></h6>
                                    <?php if (!empty($value['description'])): ?>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($value['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize Bootstrap collapse if not already initialized
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            const collapseElement = document.getElementById('jobTemplateCollapse');
            if (collapseElement && !bootstrap.Collapse.getInstance(collapseElement)) {
                new bootstrap.Collapse(collapseElement, {
                    toggle: false
                });
            }
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Render message when no template is assigned
 * 
 * @return string - HTML content
 */
function renderNoTemplateMessage() {
    ob_start();
    ?>
    <div class="card no-template-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-briefcase fa-4x text-muted mb-4"></i>
            <h5 class="text-muted mb-3">Sin Ficha de Puesto Asignada</h5>
            <p class="text-muted mb-4">
                Tu ficha de puesto aún no ha sido configurada. Contacta con el departamento de RRHH 
                para que te asignen tu job template y puedas ver tus KPIs, responsabilidades, skills y valores.
            </p>
            <div class="alert alert-info d-inline-block">
                <i class="fas fa-info-circle me-2"></i>
                Una vez asignada tu ficha de puesto, podrás dar autofeedback sobre tu desempeño.
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>