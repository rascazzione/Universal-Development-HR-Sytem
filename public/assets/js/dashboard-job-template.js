/**
 * Dashboard Job Template Functionality
 * Handles accordion state persistence and self-feedback navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    // Store collapse state in localStorage
    const collapseElement = document.getElementById('jobTemplateCollapse');
    
    if (collapseElement) {
        // Restore previous state from localStorage
        const savedState = localStorage.getItem('jobTemplateExpanded');
        if (savedState === 'true') {
            // Use Bootstrap's collapse API to show if it's not already shown
            const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
            if (bsCollapse && !collapseElement.classList.contains('show')) {
                bsCollapse.show();
            } else if (!bsCollapse && !collapseElement.classList.contains('show')) {
                collapseElement.classList.add('show');
            }
        }
        
        // Save state on toggle using Bootstrap events
        collapseElement.addEventListener('shown.bs.collapse', function() {
            localStorage.setItem('jobTemplateExpanded', 'true');
        });
        
        collapseElement.addEventListener('hidden.bs.collapse', function() {
            localStorage.setItem('jobTemplateExpanded', 'false');
        });
    }
    
    // Self-feedback button with animation
    const selfFeedbackBtn = document.querySelector('.btn-self-feedback');
    if (selfFeedbackBtn) {
        selfFeedbackBtn.addEventListener('click', function(e) {
            // Add pulse animation
            this.classList.add('pulse-animation');
            setTimeout(() => {
                this.classList.remove('pulse-animation');
            }, 600);
        });
    }
    
    // Smooth scroll to sections if hash is present
    if (window.location.hash) {
        const element = document.querySelector(window.location.hash);
        if (element) {
            setTimeout(() => {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
    
    // Initialize tooltips if any
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle keyboard navigation for accessibility
    document.addEventListener('keydown', function(e) {
        // Escape key closes the accordion
        if (e.key === 'Escape') {
            const collapseElement = document.getElementById('jobTemplateCollapse');
            if (collapseElement && collapseElement.classList.contains('show')) {
                const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    collapseElement.classList.remove('show');
                }
                // Focus back to the toggle button
                const toggleButton = document.querySelector('[data-bs-target="#jobTemplateCollapse"]');
                if (toggleButton) {
                    toggleButton.focus();
                }
            }
        }
    });
});

/**
 * Navigate to self-feedback page
 * Gets employee ID from the data attribute and redirects to feedback page
 */
function goToSelfFeedback() {
    const jobTemplateCard = document.querySelector('.job-template-accordion');
    if (jobTemplateCard) {
        const employeeId = jobTemplateCard.dataset.employeeId;
        if (employeeId) {
            window.location.href = `/employees/give-feedback.php?employee_id=${employeeId}`;
        } else {
            console.error('Employee ID not found');
            // Fallback: try to get from session or show error
            alert('No se pudo identificar tu perfil de empleado. Por favor, recarga la página e intenta nuevamente.');
        }
    } else {
        console.error('Job template card not found');
        alert('No se encontró la ficha de puesto. Por favor, recarga la página.');
    }
}

/**
 * Toggle all sections (future enhancement)
 * @param {boolean} expand - Whether to expand or collapse all sections
 */
function toggleAllSections(expand) {
    const sections = document.querySelectorAll('.template-section');
    sections.forEach(section => {
        if (expand) {
            section.classList.remove('collapsed');
        } else {
            section.classList.add('collapsed');
        }
    });
}

/**
 * Print job template
 * Opens print dialog with optimized layout
 */
function printJobTemplate() {
    // Store current state
    const collapseElement = document.getElementById('jobTemplateCollapse');
    const wasExpanded = collapseElement ? collapseElement.classList.contains('show') : false;
    
    // Expand accordion for printing
    if (collapseElement && !wasExpanded) {
        const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
        if (bsCollapse) {
            bsCollapse.show();
        } else {
            collapseElement.classList.add('show');
        }
    }
    
    // Wait a moment for the content to expand, then print
    setTimeout(() => {
        window.print();
        
        // Restore original state after print dialog closes
        setTimeout(() => {
            if (collapseElement && !wasExpanded) {
                const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    collapseElement.classList.remove('show');
                }
            }
        }, 100);
    }, 300);
}

/**
 * Export job template data as JSON (future enhancement)
 */
function exportJobTemplateData() {
    const jobTemplateCard = document.querySelector('.job-template-accordion');
    if (jobTemplateCard) {
        const employeeId = jobTemplateCard.dataset.employeeId;
        if (employeeId) {
            // Fetch the complete template data via API
            fetch(`/api/job-template.php?employee_id=${employeeId}&dimension=all`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create and download JSON file
                        const dataStr = JSON.stringify(data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = `job-template-${employeeId}-${new Date().toISOString().split('T')[0]}.json`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                    } else {
                        alert('Error exporting job template: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    alert('Error exporting job template. Please try again.');
                });
        }
    }
}

/**
 * Initialize skill level visualization
 * Adds visual indicators for skill levels
 */
function initializeSkillLevels() {
    const skillCards = document.querySelectorAll('.skill-card');
    skillCards.forEach(card => {
        const levelBadge = card.querySelector('.badge');
        if (levelBadge) {
            const level = levelBadge.textContent.toLowerCase();
            let colorClass = 'bg-secondary'; // default
            
            if (level.includes('expert') || level.includes('avanzado')) {
                colorClass = 'bg-success';
            } else if (level.includes('intermediate') || level.includes('medio')) {
                colorClass = 'bg-primary';
            } else if (level.includes('beginner') || level.includes('básico')) {
                colorClass = 'bg-warning';
            }
            
            levelBadge.className = `badge ${colorClass}`;
        }
    });
}

// Initialize skill levels when DOM is ready
document.addEventListener('DOMContentLoaded', initializeSkillLevels);

// Expose functions to global scope for onclick handlers
window.goToSelfFeedback = goToSelfFeedback;
window.toggleAllSections = toggleAllSections;
window.printJobTemplate = printJobTemplate;
window.exportJobTemplateData = exportJobTemplateData;