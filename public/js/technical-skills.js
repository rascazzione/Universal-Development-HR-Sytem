(function () {
    window.SkillModules = window.SkillModules || {};

    const TechnicalSkills = {
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
            categories: [],
            competencies: [],
            levels: []
        },
        currentAction: 'add',
        selectedAssignmentId: null,

        init(config = {}) {
            this.root = config.root;
            if (!this.root) return;

            this.modalEl = document.getElementById('technicalSkillModal');
            this.form = document.getElementById('technical-skill-form');
            this.addButton = document.getElementById('add-technical-skill-btn');
            this.countBadge = document.getElementById('technical-skills-count');

            if (!this.modalEl || !this.form) {
                console.warn('Technical skill modal elements not found.');
                return;
            }

            this.fetchUrl = this.root.dataset.fetchUrl || '/api/technical-skills.php';
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
                const target = event.target.closest('[data-action]');
                if (!target) return;
                const action = target.dataset.action;
                if (action === 'remove-technical') {
                    event.preventDefault();
                    const assignmentId = parseInt(target.dataset.assignmentId, 10);
                    if (assignmentId) {
                        this.removeAssignment(assignmentId, target);
                    }
                } else if (action === 'edit-technical') {
                    event.preventDefault();
                    const assignmentId = parseInt(target.dataset.assignmentId, 10);
                    if (assignmentId) {
                        this.openEditModal(assignmentId);
                    }
                }
            });

            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!this.templateId) {
                    SkillModules.Common?.showAlert('Save the job template before adding skills.', 'warning');
                    return;
                }
                this.submitForm();
            });

            this.form.addEventListener('input', (event) => {
                if (event.target.matches('#technical-competency-select') || event.target.matches('input[name="technical_level_id"]')) {
                    this.updatePreview();
                }
            });

            this.form.addEventListener('change', (event) => {
                if (event.target.id === 'technical-category-select') {
                    this.populateCompetencySelect(event.target.value);
                }
                if (event.target.id === 'technical-competency-select') {
                    this.updatePreview();
                }
                if (event.target.name === 'technical_level_id') {
                    this.updatePreview();
                }
            });

            this.modalEl.addEventListener('shown.bs.modal', () => {
                this.form.querySelector('#technical-category-select')?.focus();
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
                this.renderEmptyStates();
                return;
            }

            this.showLoadingState();
            try {
                const response = await fetch(`${this.fetchUrl}?template_id=${this.templateId}`, {
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error(`Failed to load technical skills (${response.status})`);
                const data = await this.parseJSON(response);
                if (!data.success) throw new Error(data.message || 'Failed to load technical skills');

                this.catalog.categories = data.categories || [];
                this.catalog.competencies = data.competencies || [];
                this.catalog.levels = data.levels || [];
                this.lastLoadedSkills = data.technical_skills || [];

                this.renderSkillsList(data.technical_skills || []);
            } catch (error) {
                console.error(error);
                this.root.innerHTML = `<div class="alert alert-warning">${error.message}</div>`;
            }
        },

        showLoadingState() {
            this.root.innerHTML = `
                <div class="skills-loading-state text-center py-4">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Loading technical skills...</p>
                </div>`;
        },

        renderEmptyStates() {
            this.root.innerHTML = `<p class="text-muted mb-0">Create and save the job template to configure technical skills.</p>`;
            if (this.countBadge) {
                this.countBadge.textContent = '0 skills';
            }
        },

        renderSkillsList(skills) {
            if (!Array.isArray(skills) || skills.length === 0) {
                this.root.innerHTML = `
                    <div class="alert alert-light text-center mb-0">
                        <i class="fas fa-toolbox mb-2 text-primary"></i>
                        <p class="mb-1 fw-semibold">No technical skills yet</p>
                        <p class="text-muted small mb-0">Use the "Add Technical Skill" button to start building the catalog for this job template.</p>
                    </div>`;
                if (this.countBadge) {
                    this.countBadge.textContent = '0 skills';
                }
                return;
            }

            const groups = new Map();
            skills.forEach((skill) => {
                const category = skill.category_name || 'Uncategorized';
                if (!groups.has(category)) {
                    groups.set(category, []);
                }
                groups.get(category).push(skill);
            });

            let html = '';
            groups.forEach((items, category) => {
                html += `
                    <div class="skills-category mb-4">
                        <div class="skills-category-header d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${category}</h6>
                            <span class="badge bg-secondary-subtle text-dark">${items.length} ${items.length === 1 ? 'skill' : 'skills'}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Competency</th>
                                        <th class="text-nowrap">Required Level</th>
                                        <th class="text-nowrap text-center">Weight</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                items.forEach((item) => {
                    const symbol = item.technical_symbol_pattern || '';
                    const levelName = item.technical_level_name || 'Level';
                    const weight = typeof item.weight_percentage !== 'undefined' ? parseFloat(item.weight_percentage).toFixed(1) : '100.0';
                    html += `
                        <tr>
                            <td>
                                <strong>${item.competency_name}</strong>
                                <div class="text-muted small">${item.competency_key ? `Key: ${item.competency_key}` : ''}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="technical-symbol-display">${symbol}</span>
                                    <span>${levelName}</span>
                                </div>
                                <div class="text-muted small">${item.technical_level_description || ''}</div>
                            </td>
                            <td class="text-center">${weight}%</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" data-action="edit-technical" data-assignment-id="${item.id}" title="Edit technical skill">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" data-action="remove-technical" data-assignment-id="${item.id}" title="Remove technical skill">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                });
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;
            });

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
            this.form.classList.remove('was-validated');
            this.updateFeedback('');

            const categorySelect = this.form.querySelector('#technical-category-select');
            const competencySelect = this.form.querySelector('#technical-competency-select');
            if (categorySelect) categorySelect.removeAttribute('disabled');
            if (competencySelect) competencySelect.removeAttribute('disabled');

            this.populateCategorySelect();
            this.populateCompetencySelect();
            this.renderLevelOptions();
            this.updatePreview();

            if (assignment) {
                const weightInput = this.form.querySelector('#technical-weight-input');

                const competency = this.catalog.competencies.find((c) => c.id === assignment.competency_id);
                if (competency && categorySelect) {
                    categorySelect.value = competency.category_id || '';
                    this.populateCompetencySelect(categorySelect.value, assignment.competency_id);
                }
                if (competencySelect) {
                    competencySelect.value = assignment.competency_id;
                }
                if (weightInput) {
                    weightInput.value = assignment.weight_percentage ?? 100;
                }

                if (assignment.technical_level_id) {
                    const levelRadio = this.form.querySelector(`input[name="technical_level_id"][value="${assignment.technical_level_id}"]`);
                    if (levelRadio) {
                        levelRadio.checked = true;
                    }
                }
                this.updatePreview();
            }

            this.modal.show();
        },

        populateCategorySelect(selectedValue = '') {
            const select = this.form.querySelector('#technical-category-select');
            if (!select) return;
            const options = ['<option value="">Select category...</option>'];
            this.catalog.categories.forEach((category) => {
                options.push(`<option value="${category.id}" ${category.id == selectedValue ? 'selected' : ''}>${category.category_name}</option>`);
            });
            select.innerHTML = options.join('');
        },

        populateCompetencySelect(categoryId = '', selectedCompetencyId = '') {
            const select = this.form.querySelector('#technical-competency-select');
            if (!select) return;
            const options = ['<option value="">Select competency...</option>'];
            this.catalog.competencies
                .filter((competency) => !categoryId || competency.category_id == categoryId)
                .forEach((competency) => {
                    options.push(`<option value="${competency.id}" ${competency.id == selectedCompetencyId ? 'selected' : ''}>${competency.competency_name}</option>`);
                });
            select.innerHTML = options.join('');
        },

        renderLevelOptions() {
            const container = this.form.querySelector('#technical-level-options');
            if (!container) return;
            if (!this.catalog.levels.length) {
                container.innerHTML = `<div class="alert alert-warning mb-0">No technical level definitions found. Please populate the reference table first.</div>`;
                return;
            }
            const options = this.catalog.levels
                .map((level, index) => {
                    const checked = index === 0 ? 'checked' : '';
                    return `
                        <label class="technical-level-card">
                            <input type="radio" name="technical_level_id" value="${level.id}" ${checked} required>
                            <span class="technical-level-content">
                                <span class="technical-level-symbol">${level.symbol_pattern || ''}</span>
                                <span class="technical-level-name">${level.level_name}</span>
                                <span class="technical-level-description text-muted">${level.description || ''}</span>
                            </span>
                        </label>`;
                })
                .join('');
            container.innerHTML = options;
        },

        updatePreview() {
            const competencySelect = this.form.querySelector('#technical-competency-select');
            const levelInput = this.form.querySelector('input[name="technical_level_id"]:checked');
            const previewName = this.form.querySelector('#technical-preview-name');
            const previewCategory = this.form.querySelector('#technical-preview-category');
            const previewSymbol = this.form.querySelector('#technical-preview-symbol');
            const previewLevel = this.form.querySelector('#technical-preview-level');
            const previewDescription = this.form.querySelector('#technical-preview-description');

            const competency = this.catalog.competencies.find((item) => item.id == (competencySelect?.value || ''));
            const level = this.catalog.levels.find((item) => item.id == (levelInput?.value || ''));

            if (previewName) {
                previewName.textContent = competency ? competency.competency_name : 'Select a competency';
            }
            if (previewCategory) {
                const category = competency ? this.catalog.categories.find((cat) => cat.id == competency.category_id) : null;
                previewCategory.textContent = category ? category.category_name : '';
            }
            if (previewSymbol) {
                previewSymbol.textContent = level ? level.symbol_pattern : '🧩⚪️⚪️⚪️⚪️';
            }
            if (previewLevel) {
                previewLevel.textContent = level ? level.level_name : 'Level';
            }
            if (previewDescription) {
                previewDescription.textContent = level ? level.description : 'Choose a level to see the expected proficiency description.';
            }
        },

        async submitForm() {
            if (!this.form.checkValidity()) {
                this.form.classList.add('was-validated');
                return;
            }

            const competencyId = parseInt(this.form.querySelector('#technical-competency-select')?.value || '0', 10);
            const technicalLevelId = parseInt(this.form.querySelector('input[name="technical_level_id"]:checked')?.value || '0', 10);
            const weight = parseFloat(this.form.querySelector('#technical-weight-input')?.value || '100');

            if (!competencyId || !technicalLevelId) {
                this.updateFeedback('Please select a competency and level.', 'danger');
                return;
            }

            const payload = {
                action: this.currentAction,
                template_id: this.templateId,
                competency_id: competencyId,
                technical_level_id: technicalLevelId,
                weight_percentage: weight,
                csrf_token: this.csrfToken
            };

            if (this.currentAction === 'update' && this.selectedAssignmentId) {
                payload.assignment_id = this.selectedAssignmentId;
            }

            this.updateFeedback('Saving technical skill...', 'info');
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
                    throw new Error(result.message || 'Failed to save technical skill');
                }
                this.modal.hide();
                SkillModules.Common?.showAlert(result.message || 'Technical skill saved.', 'success');
                this.loadData();
            } catch (error) {
                console.error(error);
                this.updateFeedback(error.message, 'danger');
            } finally {
                this.form?.classList.remove('loading');
            }
        },

        async removeAssignment(assignmentId, triggerEl) {
            if (!confirm('Remove this technical skill from the template?')) {
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
                    throw new Error(result.message || 'Failed to remove technical skill');
                }
                SkillModules.Common?.showAlert(result.message || 'Technical skill removed.', 'success');
                this.loadData();
            } catch (error) {
                console.error(error);
                SkillModules.Common?.showAlert(error.message, 'danger');
            } finally {
                triggerEl?.removeAttribute('disabled');
            }
        },

        openEditModal(assignmentId) {
            if (!Array.isArray(this.lastLoadedSkills)) {
                this.lastLoadedSkills = [];
            }
            const skill = (this.lastLoadedSkills || []).find((item) => item.id === assignmentId);
            if (skill) {
                this.currentAction = 'update';
                this.selectedAssignmentId = assignmentId;
                this.prepareModal(skill);
            } else {
                SkillModules.Common?.showAlert('Unable to locate the technical skill to edit.', 'warning');
            }
        },

        updateFeedback(message, type = 'info') {
            const feedback = this.form.querySelector('#technical-skill-feedback');
            if (!feedback) return;
            feedback.textContent = message || '';
            feedback.className = `text-${type} small`;
        }
    };

    window.SkillModules.Technical = TechnicalSkills;
})();
