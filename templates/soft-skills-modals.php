<?php
$softTemplateId = isset($editTemplateId) ? (int)$editTemplateId : 0;
$softCsrf = $skillsCsrfToken ?? generateCSRFToken();
?>

<div class="modal fade soft-skill-modal" id="softSkillModal" tabindex="-1" aria-labelledby="softSkillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="softSkillModalLabel">Add Soft Skill Competency</h5>
                    <small class="text-muted">Select a behavioral competency, review its definition, and choose the expected level.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="soft-skill-form" novalidate action="/api/soft-skills.php" data-no-ajax="true" data-no-loading="true">
                <input type="hidden" name="template_id" value="<?php echo $softTemplateId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($softCsrf); ?>">
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label for="soft-skill-select" class="form-label fw-semibold">Soft Skill Competency</label>
                            <select id="soft-skill-select" class="form-select" required>
                                <option value="">Select competency...</option>
                            </select>
                            <div class="soft-skill-overview mt-3 p-3 border rounded bg-light" id="soft-skill-overview">
                                <h6 class="mb-1" id="soft-skill-name">Select a competency</h6>
                                <p class="text-muted small mb-2" id="soft-skill-key"></p>
                                <p class="mb-2" id="soft-skill-definition"></p>
                                <p class="text-muted small mb-0" id="soft-skill-description"></p>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="soft-skill-levels" id="soft-skill-level-options">
                                <div class="skills-loading-state text-center py-4">
                                    <div class="spinner-border text-primary mb-2" role="status"></div>
                                    <p class="text-muted mb-0">Loading competency levels...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="soft-skill-summary border rounded p-3" id="soft-skill-summary">
                                <h6 class="mb-2">Summary</h6>
                                <p class="mb-1">Competency: <strong id="soft-summary-name">Select a competency</strong></p>
                                <p class="mb-1">Selected Level: <strong id="soft-summary-level">None</strong></p>
                                <p class="mb-1">Visual Indicator: <span id="soft-summary-symbol" class="soft-symbol-display">🧠⚪️⚪️⚪️</span></p>
                                <div><span class="text-muted small">Key Behaviors:</span>
                                    <ul class="soft-summary-behaviors small mb-0" id="soft-summary-behaviors"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="soft-weight-input" class="form-label fw-semibold">Weight Allocation (%)</label>
                            <input type="number" class="form-control" id="soft-weight-input" name="weight_percentage" value="100" min="0" max="100" step="0.5">
                            <div class="form-text">Optional weighting for this soft skill within the competency section.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted me-auto small" id="soft-skill-feedback"></span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?php echo $softTemplateId ? '' : 'disabled'; ?>>Save Soft Skill</button>
                </div>
            </form>
        </div>
    </div>
</div>
