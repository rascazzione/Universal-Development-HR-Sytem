(function () {
    window.SkillModules = window.SkillModules || {};

    const Common = {
        alertContainer: null,

        init() {
            this.alertContainer = document.getElementById('alerts-container');
        },

        showAlert(message, type = 'success', timeout = 5000) {
            if (!this.alertContainer) return;

            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            const alertEl = wrapper.firstElementChild;
            this.alertContainer.appendChild(alertEl);

            if (timeout) {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                    bsAlert.close();
                }, timeout);
            }
        }
    };

    window.SkillModules.Common = Common;

    document.addEventListener('DOMContentLoaded', () => {
        Common.init();

        // Initialize Bootstrap tooltips within the page
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl) => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        const technicalRoot = document.getElementById('technical-skills-root');
        if (technicalRoot && window.SkillModules.Technical) {
            window.SkillModules.Technical.init({ root: technicalRoot });
        }

        const softRoot = document.getElementById('soft-skills-root');
        if (softRoot && window.SkillModules.Soft) {
            window.SkillModules.Soft.init({ root: softRoot });
        }
    });
})();
