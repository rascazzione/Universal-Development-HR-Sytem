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

    // Normalize data from JobTemplate to keep the renderer backwards-compatible
    $formatKpi = static function(array $kpi): array {
        $title = $kpi['title']
            ?? $kpi['kpi_name']
            ?? $kpi['name']
            ?? '';

        $description = $kpi['description']
            ?? $kpi['kpi_description']
            ?? $kpi['details']
            ?? '';

        $targetValue = $kpi['target']
            ?? $kpi['target_value']
            ?? $kpi['expected_result']
            ?? '';

        $targetUnit = $kpi['measurement_unit']
            ?? $kpi['unit']
            ?? '';

        $target = trim(trim((string) $targetValue) . ' ' . trim((string) $targetUnit));
        if ($target === '' && isset($kpi['weight_percentage'])) {
            $target = rtrim(rtrim((string) $kpi['weight_percentage'], '0'), '.');
            $target = ($target === '' ? (string) $kpi['weight_percentage'] : $target) . '%';
        }

        return [
            'title' => $title,
            'description' => $description,
            'target' => $target
        ];
    };

    $formatResponsibility = static function(array $responsibility): array {
        $title = $responsibility['title'] ?? '';
        $description = $responsibility['description'] ?? '';
        $text = $responsibility['responsibility_text'] ?? ($responsibility['text'] ?? '');

        if ($title === '' && $text !== '') {
            $title = $text;
        } elseif ($description === '' && $text !== '' && $text !== $title) {
            $description = $text;
        }

        return [
            'title' => $title,
            'description' => $description
        ];
    };

    $formatSkill = static function(array $skill): array {
        $name = $skill['skill_name']
            ?? $skill['competency_name']
            ?? $skill['name']
            ?? '';

        $levelCandidates = [
            $skill['level'] ?? null,
            $skill['required_level'] ?? null,
            $skill['technical_level_name'] ?? null,
            isset($skill['technical_display_level']) ? 'Nivel ' . $skill['technical_display_level'] : null,
            $skill['soft_skill_level_title'] ?? null,
            isset($skill['soft_skill_level']) ? 'Nivel ' . $skill['soft_skill_level'] : null
        ];

        $level = null;
        foreach ($levelCandidates as $candidate) {
            if (!empty($candidate)) {
                $level = $candidate;
                break;
            }
        }

        $description = $skill['description']
            ?? $skill['competency_description']
            ?? $skill['technical_level_description']
            ?? $skill['soft_skill_description']
            ?? $skill['soft_skill_definition']
            ?? $skill['soft_skill_meaning']
            ?? null;

        if (!$description && !empty($skill['soft_skill_behaviors']) && is_array($skill['soft_skill_behaviors'])) {
            $description = implode(', ', $skill['soft_skill_behaviors']);
        }

        return [
            'name' => $name,
            'level' => $level,
            'description' => $description
        ];
    };

    $kpiItems = array_map($formatKpi, $details['kpis'] ?? []);
    $responsibilityItems = array_map($formatResponsibility, $details['responsibilities'] ?? []);
    $technicalSkillItems = array_map($formatSkill, $details['technical_skills'] ?? []);
    $softSkillItems = array_map($formatSkill, $details['soft_skills'] ?? []);
    $valueItems = $details['values'] ?? [];
    
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
                        📋 My Job Profile - <?php echo htmlspecialchars($template['position_title']); ?>
                    </button>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark"><?php echo $totalSections; ?> Sections</span>
                    <button class="btn btn-sm btn-success btn-self-feedback" onclick="goToSelfFeedback()">
                        <i class="fas fa-star me-1"></i> Give Self-Feedback
                    </button>
                </div>
            </div>
        </div>
        
        <div id="jobTemplateCollapse" class="collapse <?php echo $isExpanded ? 'show' : ''; ?>" 
             aria-labelledby="jobTemplateHeader" data-bs-parent="#dashboardAccordion">
            <div class="card-body">
                <?php if (!empty($template['description'])): ?>
                <div class="alert alert-info mb-4">
                    <small class="d-block mb-1"><strong>Job Description:</strong></small>
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
                
                <!-- Section 1: KPIs -->
                <?php if (!empty($kpiItems)): ?>
                <div class="template-section">
                    <h6><i class="fas fa-chart-line me-2"></i>📊 Desired Results (KPIs)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>KPI</th>
                                    <th>Description</th>
                                    <th>Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kpiItems as $kpi): ?>
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
                
                <!-- Section 2: Responsibilities -->
                <?php if (!empty($responsibilityItems)): ?>
                <div class="template-section">
                    <h6><i class="fas fa-tasks me-2"></i>📝 Key Responsibilities</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($responsibilityItems as $responsibility): ?>
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
                
                <!-- Section 3: Skills (Technical + Soft) -->
                <?php if (!empty($technicalSkillItems) || !empty($softSkillItems)): ?>
                <div class="template-section">
                    <h6><i class="fas fa-puzzle-piece me-2"></i>🧩 Skills & Competencies</h6>
                    
                    <!-- Tabs for Technical/Soft Skills -->
                    <ul class="nav nav-tabs mb-3" id="skillsTabs" role="tablist">
                        <?php if (!empty($technicalSkillItems)): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="technical-tab" data-bs-toggle="tab" 
                                    data-bs-target="#technical-skills" type="button" role="tab">
                                <i class="fas fa-code me-1"></i>Technical Skills
                                <span class="badge bg-secondary ms-1"><?php echo count($technicalSkillItems); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($softSkillItems)): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo empty($technicalSkillItems) ? 'active' : ''; ?>" 
                                    id="soft-tab" data-bs-toggle="tab" 
                                    data-bs-target="#soft-skills" type="button" role="tab">
                                <i class="fas fa-users me-1"></i>Soft Skills
                                <span class="badge bg-secondary ms-1"><?php echo count($softSkillItems); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="tab-content" id="skillsTabsContent">
                        <?php if (!empty($technicalSkillItems)): ?>
                        <div class="tab-pane fade show active" id="technical-skills" role="tabpanel">
                            <div class="skills-grid">
                                <?php foreach ($technicalSkillItems as $skill): ?>
                                <div class="skill-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($skill['name'] ?? ''); ?></h6>
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
                        
                        <?php if (!empty($softSkillItems)): ?>
                        <div class="tab-pane fade <?php echo empty($technicalSkillItems) ? 'show active' : ''; ?>" 
                             id="soft-skills" role="tabpanel">
                            <div class="skills-grid">
                                <?php foreach ($softSkillItems as $skill): ?>
                                <div class="skill-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($skill['name'] ?? ''); ?></h6>
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
                
                <!-- Section 4: Company Values -->
                <?php if (!empty($valueItems)): ?>
                <div class="template-section">
                    <h6><i class="fas fa-gem me-2"></i>💎 Company Values</h6>
                    <div class="row">
                        <?php foreach ($valueItems as $value): ?>
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
            <h5 class="text-muted mb-3">No Job Profile Assigned</h5>
            <p class="text-muted mb-4">
                Your job profile has not been configured yet. Contact the HR department
                to assign your job template so you can see your KPIs, responsibilities, skills, and values.
            </p>
            <div class="alert alert-info d-inline-block">
                <i class="fas fa-info-circle me-2"></i>
                Once your job profile is assigned, you will be able to give self-feedback on your performance.
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
