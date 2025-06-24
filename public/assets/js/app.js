/**
 * Performance Evaluation System - Main JavaScript
 */

// Global application object
const PerfEval = {
    // Configuration
    config: {
        autoSaveInterval: 30000, // 30 seconds
        confirmDeleteMessage: 'Are you sure you want to delete this item? This action cannot be undone.',
        loadingClass: 'loading',
        fadeSpeed: 300
    },
    
    // Initialize application
    init: function() {
        this.initEventListeners();
        this.initTooltips();
        this.initDataTables();
        this.initCharts();
        this.initFormValidation();
        this.initAutoSave();
        console.log('Performance Evaluation System initialized');
    },
    
    // Initialize event listeners
    initEventListeners: function() {
        // Confirmation dialogs
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm]')) {
                e.preventDefault();
                const message = e.target.getAttribute('data-confirm') || PerfEval.config.confirmDeleteMessage;
                if (confirm(message)) {
                    if (e.target.tagName === 'A') {
                        window.location.href = e.target.href;
                    } else if (e.target.form) {
                        e.target.form.submit();
                    }
                }
            }
        });
        
        // Loading states for forms
        document.addEventListener('submit', function(e) {
            if (e.target.matches('form:not([data-no-loading])')) {
                PerfEval.showLoading(e.target);
            }
        });
        
        // AJAX form submissions
        document.addEventListener('submit', function(e) {
            if (e.target.matches('form[data-ajax]')) {
                e.preventDefault();
                PerfEval.submitAjaxForm(e.target);
            }
        });
        
        // Dynamic form fields
        document.addEventListener('change', function(e) {
            if (e.target.matches('[data-dynamic-field]')) {
                PerfEval.handleDynamicField(e.target);
            }
        });
        
        // Search functionality
        document.addEventListener('input', function(e) {
            if (e.target.matches('[data-search]')) {
                PerfEval.handleSearch(e.target);
            }
        });
    },
    
    // Initialize Bootstrap tooltips
    initTooltips: function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },
    
    // Initialize DataTables
    initDataTables: function() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            if (typeof DataTable !== 'undefined') {
                new DataTable(table, {
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']],
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        }
                    }
                });
            }
        });
    },
    
    // Initialize charts
    initCharts: function() {
        // Performance rating distribution chart
        const ratingChart = document.getElementById('ratingDistributionChart');
        if (ratingChart && typeof Chart !== 'undefined') {
            const ctx = ratingChart.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement', 'Unsatisfactory'],
                    datasets: [{
                        data: [25, 35, 30, 8, 2],
                        backgroundColor: [
                            '#198754',
                            '#20c997',
                            '#ffc107',
                            '#fd7e14',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Department performance chart
        const deptChart = document.getElementById('departmentChart');
        if (deptChart && typeof Chart !== 'undefined') {
            const ctx = deptChart.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['IT', 'Sales', 'Marketing', 'HR', 'Finance'],
                    datasets: [{
                        label: 'Average Rating',
                        data: [4.2, 3.8, 4.0, 4.1, 3.9],
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5
                        }
                    }
                }
            });
        }
    },
    
    // Initialize form validation
    initFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Focus on first invalid field
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                    }
                }
                form.classList.add('was-validated');
            });
        });
    },
    
    // Initialize auto-save functionality
    initAutoSave: function() {
        const autoSaveForms = document.querySelectorAll('form[data-autosave]');
        autoSaveForms.forEach(form => {
            const formId = form.getAttribute('data-autosave');
            
            // Load saved data
            this.loadAutoSaveData(form, formId);
            
            // Set up auto-save
            let saveTimeout;
            form.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    PerfEval.saveFormData(form, formId);
                }, 2000); // Save 2 seconds after last input
            });
            
            // Clear saved data on successful submit
            form.addEventListener('submit', function() {
                PerfEval.clearAutoSaveData(formId);
            });
        });
    },
    
    // Save form data to localStorage
    saveFormData: function(form, formId) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'csrf_token') {
                data[key] = value;
            }
        }
        
        localStorage.setItem(`autosave_${formId}`, JSON.stringify({
            data: data,
            timestamp: Date.now()
        }));
        
        this.showAutoSaveIndicator('saved');
    },
    
    // Load auto-saved data
    loadAutoSaveData: function(form, formId) {
        const saved = localStorage.getItem(`autosave_${formId}`);
        if (saved) {
            try {
                const { data, timestamp } = JSON.parse(saved);
                
                // Only restore if saved within last 24 hours
                if (Date.now() - timestamp < 24 * 60 * 60 * 1000) {
                    for (let [key, value] of Object.entries(data)) {
                        const field = form.querySelector(`[name="${key}"]`);
                        if (field) {
                            if (field.type === 'checkbox' || field.type === 'radio') {
                                field.checked = value === field.value;
                            } else {
                                field.value = value;
                            }
                        }
                    }
                    this.showAutoSaveIndicator('restored');
                }
            } catch (e) {
                console.error('Error loading auto-save data:', e);
            }
        }
    },
    
    // Clear auto-saved data
    clearAutoSaveData: function(formId) {
        localStorage.removeItem(`autosave_${formId}`);
    },
    
    // Show auto-save indicator
    showAutoSaveIndicator: function(type) {
        let indicator = document.getElementById('autosave-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'autosave-indicator';
            indicator.className = 'position-fixed top-0 end-0 m-3';
            indicator.style.zIndex = '1050';
            document.body.appendChild(indicator);
        }
        
        const messages = {
            saved: '<i class="fas fa-check"></i> Draft saved',
            restored: '<i class="fas fa-info-circle"></i> Draft restored',
            error: '<i class="fas fa-exclamation-triangle"></i> Save failed'
        };
        
        const classes = {
            saved: 'alert alert-success',
            restored: 'alert alert-info',
            error: 'alert alert-danger'
        };
        
        indicator.innerHTML = messages[type] || messages.saved;
        indicator.className = `position-fixed top-0 end-0 m-3 ${classes[type] || classes.saved}`;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => {
                indicator.innerHTML = '';
                indicator.className = 'position-fixed top-0 end-0 m-3';
                indicator.style.opacity = '1';
            }, 300);
        }, 3000);
    },
    
    // Handle AJAX form submissions
    submitAjaxForm: function(form) {
        const url = form.action || window.location.href;
        const method = form.method || 'POST';
        const formData = new FormData(form);
        
        this.showLoading(form);
        
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading(form);
            
            if (data.success) {
                this.showAlert('success', data.message || 'Operation completed successfully');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
            } else {
                this.showAlert('danger', data.message || 'An error occurred');
            }
        })
        .catch(error => {
            this.hideLoading(form);
            this.showAlert('danger', 'Network error occurred');
            console.error('AJAX error:', error);
        });
    },
    
    // Handle dynamic form fields
    handleDynamicField: function(field) {
        const target = field.getAttribute('data-dynamic-field');
        const targetElement = document.getElementById(target);
        
        if (targetElement) {
            const value = field.value;
            
            // Show/hide based on value
            if (field.hasAttribute('data-show-when')) {
                const showWhen = field.getAttribute('data-show-when');
                if (value === showWhen) {
                    targetElement.style.display = 'block';
                } else {
                    targetElement.style.display = 'none';
                }
            }
            
            // Load dynamic content
            if (field.hasAttribute('data-load-url')) {
                const url = field.getAttribute('data-load-url');
                this.loadDynamicContent(url + '?value=' + encodeURIComponent(value), targetElement);
            }
        }
    },
    
    // Handle search functionality
    handleSearch: function(searchField) {
        const query = searchField.value.toLowerCase();
        const target = searchField.getAttribute('data-search');
        const items = document.querySelectorAll(target);
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    },
    
    // Load dynamic content
    loadDynamicContent: function(url, container) {
        this.showLoading(container);
        
        fetch(url)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            this.hideLoading(container);
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">Error loading content</div>';
            this.hideLoading(container);
            console.error('Dynamic content error:', error);
        });
    },
    
    // Show loading state
    showLoading: function(element) {
        element.classList.add(this.config.loadingClass);
        
        // Add spinner overlay for containers
        if (element.tagName !== 'FORM') {
            const spinner = document.createElement('div');
            spinner.className = 'spinner-overlay';
            spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            element.style.position = 'relative';
            element.appendChild(spinner);
        }
    },
    
    // Hide loading state
    hideLoading: function(element) {
        element.classList.remove(this.config.loadingClass);
        
        // Remove spinner overlay
        const spinner = element.querySelector('.spinner-overlay');
        if (spinner) {
            spinner.remove();
        }
    },
    
    // Show alert message
    showAlert: function(type, message, container = null) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml);
        } else {
            // Add to top of main content
            const main = document.querySelector('main');
            if (main) {
                main.insertAdjacentHTML('afterbegin', alertHtml);
            }
        }
    },
    
    // Utility functions
    utils: {
        // Format number as currency
        formatCurrency: function(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },
        
        // Format date
        formatDate: function(date, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
        },
        
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Generate random ID
        generateId: function(prefix = 'id') {
            return prefix + '_' + Math.random().toString(36).substr(2, 9);
        },
        
        // Validate email
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        // Copy to clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    PerfEval.showAlert('success', 'Copied to clipboard');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                PerfEval.showAlert('success', 'Copied to clipboard');
            }
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    PerfEval.init();
});

// Export for use in other scripts
window.PerfEval = PerfEval;