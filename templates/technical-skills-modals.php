<?php
$technicalTemplateId = isset($editTemplateId) ? (int)$editTemplateId : 0;
$technicalCsrf = $skillsCsrfToken ?? generateCSRFToken();
?>

<div class="modal fade" id="technicalSkillModal" tabindex="-1" aria-labelledby="technicalSkillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="technicalSkillModalLabel">Add Technical Skill</h5>
                    <small class="text-muted">Select a technical competency and define the expected proficiency level.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="technical-skill-form" novalidate action="/api/technical-skills.php" data-no-ajax="true" data-no-loading="true">
                <input type="hidden" name="template_id" value="<?php echo $technicalTemplateId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($technicalCsrf); ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="technical-category-select" class="form-label fw-semibold">Skill Category</label>
                            <select id="technical-category-select" class="form-select" required>
                                <option value="">Select category...</option>
                            </select>
                            <div class="form-text">Categories are loaded from the technical competency catalog.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="technical-competency-select" class="form-label fw-semibold">Technical Competency</label>
                            <select id="technical-competency-select" class="form-select" required>
                                <option value="">Select competency...</option>
                            </select>
                            <div class="form-text">Choose the competency you want to add to this template.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex align-items-center justify-content-between">
                                <span>Required Level</span>
                                <a href="#" class="link-secondary small" id="technical-level-help" data-bs-toggle="tooltip" title="Levels follow a 5-step proficiency scale from awareness to mastery.">How is this calculated?</a>
                            </label>
                            <div id="technical-level-options" class="technical-level-options">
                                <div class="skills-loading-state text-center py-4">
                                    <div class="spinner-border text-primary mb-2" role="status"></div>
                                    <p class="text-muted mb-0">Loading level definitions...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="technical-weight-input" class="form-label fw-semibold">Weight Allocation (%)</label>
                            <input type="number" class="form-control" id="technical-weight-input" name="weight_percentage" value="100" min="0" max="100" step="0.5">
                            <div class="form-text">Optional weighting for this skill within the overall competency section.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="technical-preview card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle text-muted mb-2">Preview</h6>
                                    <p class="mb-1"><strong id="technical-preview-name">Select a competency</strong></p>
                                    <p class="mb-2 text-muted small" id="technical-preview-category"></p>
                                    <div class="technical-preview-level d-flex align-items-center gap-2">
                                        <span id="technical-preview-symbol" class="technical-symbol-display">🧩⚪️⚪️⚪️⚪️</span>
                                        <span id="technical-preview-level">Level</span>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0" id="technical-preview-description">Choose a level to see the expected proficiency description.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted me-auto small" id="technical-skill-feedback"></span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?php echo $technicalTemplateId ? '' : 'disabled'; ?>>Save Technical Skill</button>
                </div>
            </form>
        </div>
    </div>
</div>
