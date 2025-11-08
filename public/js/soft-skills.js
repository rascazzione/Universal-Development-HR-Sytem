(function () {
    window.SkillModules = window.SkillModules || {};

    const SoftSkills = {
        root: null,
        modalEl: null,
        modal: null,
        form: null,
        addButton: null,
        countBadge: null,
        fetchUrl: '',
        csrfToken: '',
        templateId: 0,
        catalog: {
            competencies: new Map()
        },
        assigned: [],
        currentAction: 'add',
        selectedAssignmentId: null,

        init(config = {}) {
            this.root = config.root;
            if (!this.root) return;

            this.modalEl = document.getElementById('softSkillModal');
            this.form = document.getElementById('soft-skill-form');
            this.addButton = document.getElementById('add-soft-skill-btn');
            this.countBadge = document.getElementById('soft-skills-count');

            if (!this.modalEl || !this.form) {
                console.warn('Soft skill modal elements not found.');
                return;
            }

            this.fetchUrl = this.root.dataset.fetchUrl || '/api/soft-skills.php';
            this.csrfToken = this.root.dataset.csrf || '';
            this.templateId = parseInt(this.root.dataset.templateId, 10) || 0;

            this.modal = bootstrap.Modal.getOrCreateInstance(this.modalEl);

            this.bindEvents();
            this.loadData();
        },

        bindEvents() {
            if (this.addButton) {
                this.addButton.addEventListener('click', () => {
                    this.currentAction = 'add';
                    this.selectedAssignmentId = null;
                    this.prepareModal();
                });
            }

            this.root.addEventListener('click', (event) => {
                const actionBtn = event.target.closest('[data-action]');
                if (!actionBtn) return;
                event.preventDefault();
                const assignmentId = parseInt(actionBtn.dataset.assignmentId, 10);
                if (!assignmentId) return;
                if (actionBtn.dataset.action === 'remove-soft') {
                    this.removeAssignment(assignmentId, actionBtn);
                } else if (actionBtn.dataset.action === 'edit-soft') {
                    this.openEditModal(assignmentId);
                }
            });

            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!this.templateId) {
                    SkillModules.Common?.showAlert('Save the job template before assigning soft skills.', 'warning');
                    return;
                }
                this.submitForm();
            });

            this.form.addEventListener('change', (event) => {
                if (event.target.id === 'soft-skill-select') {
                    this.renderLevelOptions(event.target.value);
                    this.updateSummary();
                }
                if (event.target.name === 'soft_skill_level') {
                    this.updateSummary();
                }
            });
        },

        async parseJSON(response) {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (error) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server.');
            }
        },

        async loadData() {
            if (!this.templateId) {
                this.renderEmptyState();
                return;
            }

            this.showLoadingState();
            try {
                const response = await fetch(`${this.fetchUrl}?template_id=${this.templateId}`, {
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error(`Failed to load soft skills (${response.status})`);
                const data = await this.parseJSON(response);
                if (!data.success) throw new Error(data.message || 'Failed to load soft skills');

                this.assigned = data.soft_skills || [];
                this.buildCompetencyCatalog(data.available_competencies || []);
                this.renderSoftSkillsList(this.assigned);
            } catch (error) {
                console.error(error);
                this.root.innerHTML = `<div class="alert alert-warning">${error.message}</div>`;
            }
        },

        showLoadingState() {
            this.root.innerHTML = `
                <div class="skills-loading-state text-center py-4">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Loading soft skills...</p>
                </div>`;
        },

        renderEmptyState() {
            this.root.innerHTML = `<p class="text-muted mb-0">Save this job template to begin assigning soft skills.</p>`;
            if (this.countBadge) {
                this.countBadge.textContent = '0 skills';
            }
        },

        buildCompetencyCatalog(competencies) {
            this.catalog.competencies = new Map();
            competencies.forEach((item) => {
                const levelsArray = [];
                if (item.levels) {
                    Object.keys(item.levels).forEach((levelKey) => {
                        const level = item.levels[levelKey] || {};
                        levelsArray.push({
                            level: parseInt(levelKey, 10),
                            title: level.title || `Level ${levelKey}`,
                            behaviors: Array.isArray(level.behaviors) ? level.behaviors : [],
                            symbol_pattern: this.buildSoftSymbol(parseInt(levelKey, 10))
                        });
                    });
                    levelsArray.sort((a, b) => a.level - b.level);
                }
                this.catalog.competencies.set(String(item.id), {
                    id: item.id,
                    competency_key: item.competency_key,
                    name: item.name,
                    definition: item.definition,
                    description: item.description,
                    levels: levelsArray
                });
            });
        },

        renderSoftSkillsList(skills) {
            if (!Array.isArray(skills) || skills.length === 0) {
                this.root.innerHTML = `
                    <div class="alert alert-light text-center mb-0">
                        <i class="fas fa-brain mb-2 text-primary"></i>
                        <p class="mb-1 fw-semibold">No soft skills configured</p>
                        <p class="text-muted small mb-0">Use the "Add Soft Skill" button to select competencies from the catalog.</p>
                    </div>`;
                if (this.countBadge) {
                    this.countBadge.textContent = '0 skills';
                }
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Competency</th>
                                <th>Level</th>
                                <th class="text-start">Observable Behaviors</th>
                                <th class="text-center">Weight</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>`;

            skills.forEach((skill) => {
                const behaviors = Array.isArray(skill.soft_skill_behaviors) ? skill.soft_skill_behaviors : [];
                const behaviorList = behaviors.slice(0, 3).map((behavior) => `<li>${behavior}</li>`).join('');
                const remaining = behaviors.length > 3 ? `<li class="text-muted">+${behaviors.length - 3} more</li>` : '';
                const weight = typeof skill.weight_percentage !== 'undefined' ? parseFloat(skill.weight_percentage).toFixed(1) : '100.0';

                html += `
                    <tr>
                        <td>
                            <strong>${skill.competency_name}</strong>
                            <div class="text-muted small">Key: ${skill.competency_key || 'n/a'}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="soft-symbol-display">${skill.soft_skill_symbol_pattern || ''}</span>
                                <span>
                                    ${skill.soft_skill_level_title || ''}
                                    <span class="d-block text-muted small">${skill.soft_skill_meaning || ''}</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <ul class="soft-behavior-list mb-0">
                                ${behaviorList}${remaining}
                            </ul>
                        </td>
                        <td class="text-center">${weight}%</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-secondary" data-action="edit-soft" data-assignment-id="${skill.id}" title="Edit soft skill">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-outline-danger" data-action="remove-soft" data-assignment-id="${skill.id}" title="Remove soft skill">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table></div>';
            this.root.innerHTML = html;
            if (this.countBadge) {
                const total = skills.length;
                this.countBadge.textContent = `${total} ${total === 1 ? 'skill' : 'skills'}`;
            }
        },

        prepareModal(assignment = null) {
            if (this.form) {
                this.form.classList.remove('loading');
            }
            this.form.reset();
            this.updateFeedback('');
            const competencySelect = this.form.querySelector('#soft-skill-select');
            if (competencySelect) {
                competencySelect.removeAttribute('disabled');
            }
            this.populateCompetencySelect();
            this.renderLevelOptions(this.form.querySelector('#soft-skill-select')?.value || '');
            this.updateSummary();
            if (assignment) {
                this.fillFormForEdit(assignment);
            }
            this.modal.show();
        },

        populateCompetencySelect(selectedId = '') {
            const select = this.form.querySelector('#soft-skill-select');
            if (!select) return;
            const options = ['<option value="">Select competency...</option>'];
            const sorted = Array.from(this.catalog.competencies.values()).sort((a, b) => a.name.localeCompare(b.name));
            sorted.forEach((competency) => {
                options.push(`<option value="${competency.id}" ${competency.id == selectedId ? 'selected' : ''}>${competency.name}</option>`);
            });
            select.innerHTML = options.join('');
        },

        renderLevelOptions(competencyId) {
            const container = this.form.querySelector('#soft-skill-level-options');
            const overviewName = this.form.querySelector('#soft-skill-name');
            const overviewKey = this.form.querySelector('#soft-skill-key');
            const overviewDefinition = this.form.querySelector('#soft-skill-definition');
            const overviewDescription = this.form.querySelector('#soft-skill-description');

            const competency = this.catalog.competencies.get(String(competencyId));
            if (!competency) {
                container.innerHTML = `<div class="alert alert-info mb-0">Select a competency to display its levels and behaviors.</div>`;
                if (overviewName) overviewName.textContent = 'Select a competency';
                if (overviewKey) overviewKey.textContent = '';
                if (overviewDefinition) overviewDefinition.textContent = '';
                if (overviewDescription) overviewDescription.textContent = '';
                return;
            }

            if (overviewName) overviewName.textContent = competency.name;
            if (overviewKey) overviewKey.textContent = `Key: ${competency.competency_key}`;
            if (overviewDefinition) overviewDefinition.textContent = competency.definition || '';
            if (overviewDescription) overviewDescription.textContent = competency.description || '';

            if (!competency.levels.length) {
                container.innerHTML = `<div class="alert alert-warning mb-0">No level definitions available for this competency.</div>`;
                return;
            }

            const options = competency.levels.map((level, index) => {
                const behaviors = level.behaviors.map((behavior) => `<li>${behavior}</li>`).join('');
                const checked = index === 0 ? 'checked' : '';
                return `
                    <label class="soft-level-card">
                        <input type="radio" name="soft_skill_level" value="${level.level}" ${checked} required>
                        <span class="soft-level-content">
                            <span class="soft-level-header">
                                <span class="soft-symbol-display">${level.symbol_pattern}</span>
                                <span>
                                    <span class="soft-level-title">Level ${level.level} — ${level.title}</span>
                                    <span class="soft-level-meaning">${this.meaningForLevel(level.level)}</span>
                                </span>
                            </span>
                            <ul class="soft-behavior-list mb-0">${behaviors}</ul>
                        </span>
                    </label>`;
            }).join('');

            container.innerHTML = options;
        },

        meaningForLevel(level) {
            switch (parseInt(level, 10)) {
                case 1: return 'Basic';
                case 2: return 'Intermediate';
                case 3: return 'Advanced';
                case 4: return 'Expert';
                default: return 'Level';
            }
        },

        updateSummary() {
            const summaryName = this.form.querySelector('#soft-summary-name');
            const summaryLevel = this.form.querySelector('#soft-summary-level');
            const summarySymbol = this.form.querySelector('#soft-summary-symbol');
            const summaryBehaviors = this.form.querySelector('#soft-summary-behaviors');

            const competencySelect = this.form.querySelector('#soft-skill-select');
            const levelInput = this.form.querySelector('input[name="soft_skill_level"]:checked');
            const competency = this.catalog.competencies.get(String(competencySelect?.value || ''));

            if (summaryName) summaryName.textContent = competency ? competency.name : 'Select a competency';

            if (!competency || !levelInput) {
                if (summaryLevel) summaryLevel.textContent = 'None';
                if (summarySymbol) summarySymbol.textContent = '🧠⚪️⚪️⚪️';
                if (summaryBehaviors) summaryBehaviors.innerHTML = '';
                return;
            }

            const level = competency.levels.find((lvl) => lvl.level == levelInput.value);
            if (summaryLevel) summaryLevel.textContent = level ? `Level ${level.level} — ${level.title}` : 'Level';
            if (summarySymbol) summarySymbol.textContent = level ? level.symbol_pattern : '🧠⚪️⚪️⚪️';
            if (summaryBehaviors) {
                summaryBehaviors.innerHTML = (level?.behaviors || []).map((behavior) => `<li>${behavior}</li>`).join('');
            }
        },

        fillFormForEdit(assignment) {
            const competencySelect = this.form.querySelector('#soft-skill-select');
            const weightInput = this.form.querySelector('#soft-weight-input');

            if (competencySelect) {
                competencySelect.value = assignment.competency_id;
            }
            this.renderLevelOptions(assignment.competency_id);
            if (weightInput) {
                weightInput.value = assignment.weight_percentage ?? 100;
            }

            const levelRadio = this.form.querySelector(`input[name="soft_skill_level"][value="${assignment.soft_skill_level}"]`);
            if (levelRadio) {
                levelRadio.checked = true;
            }
            this.updateSummary();
        },

        async submitForm() {
            if (!this.form.checkValidity()) {
                this.form.classList.add('was-validated');
                return;
            }

            const competencyId = parseInt(this.form.querySelector('#soft-skill-select')?.value || '0', 10);
            const levelInput = this.form.querySelector('input[name="soft_skill_level"]:checked');
            const weight = parseFloat(this.form.querySelector('#soft-weight-input')?.value || '100');
            if (!competencyId || !levelInput) {
                this.updateFeedback('Select a competency and level.', 'danger');
                return;
            }

            const competency = this.catalog.competencies.get(String(competencyId));
            if (!competency) {
                this.updateFeedback('Invalid competency selection.', 'danger');
                return;
            }

            const payload = {
                action: this.currentAction,
                template_id: this.templateId,
                competency_id: competencyId,
                competency_key: competency.competency_key,
                soft_skill_level: parseInt(levelInput.value, 10),
                weight_percentage: weight,
                csrf_token: this.csrfToken
            };

            if (this.currentAction === 'update' && this.selectedAssignmentId) {
                payload.assignment_id = this.selectedAssignmentId;
            }

            this.updateFeedback('Saving soft skill...', 'info');
            try {
                const response = await fetch(this.fetchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const result = await this.parseJSON(response);
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save soft skill');
                }
                this.modal.hide();
                SkillModules.Common?.showAlert(result.message || 'Soft skill saved.', 'success');
                this.loadData();
            } catch (error) {
                console.error(error);
                this.updateFeedback(error.message, 'danger');
            } finally {
                this.form?.classList.remove('loading');
            }
        },

        async removeAssignment(assignmentId, triggerEl) {
            if (!confirm('Remove this soft skill from the template?')) {
                return;
            }

            const payload = {
                action: 'remove',
                template_id: this.templateId,
                assignment_id: assignmentId,
                csrf_token: this.csrfToken
            };

            triggerEl?.setAttribute('disabled', 'disabled');
            try {
                const response = await fetch(this.fetchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const result = await this.parseJSON(response);
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to remove soft skill');
                }
                SkillModules.Common?.showAlert(result.message || 'Soft skill removed.', 'success');
                this.loadData();
            } catch (error) {
                console.error(error);
                SkillModules.Common?.showAlert(error.message, 'danger');
            } finally {
                triggerEl?.removeAttribute('disabled');
            }
        },

        openEditModal(assignmentId) {
            const skill = this.assigned.find((item) => item.id === assignmentId);
            if (!skill) {
                SkillModules.Common?.showAlert('Unable to locate the soft skill to edit.', 'warning');
                return;
            }
            this.currentAction = 'update';
            this.selectedAssignmentId = assignmentId;
            this.prepareModal(skill);
        },

        updateFeedback(message, type = 'info') {
            const feedback = this.form.querySelector('#soft-skill-feedback');
            if (!feedback) return;
            feedback.textContent = message || '';
            feedback.className = `text-${type} small`;
        },

        buildSoftSymbol(level) {
            level = parseInt(level, 10) || 0;
            const filled = Math.max(0, Math.min(level, 4));
            const empty = Math.max(0, 4 - filled);
            return '🧠'.repeat(filled) + '⚪️'.repeat(empty);
        }
    };

    window.SkillModules.Soft = SoftSkills;
})();
