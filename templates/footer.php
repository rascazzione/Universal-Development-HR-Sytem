</main>
    
    <!-- Footer -->
    <?php if (isAuthenticated()): ?>
    <footer class="bg-light mt-5 py-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo getAppSetting('company_name', 'Your Company'); ?>. 
                        All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        <?php echo getAppSetting('system_name', APP_NAME); ?> v<?php echo APP_VERSION; ?>
                        <?php if (isHRAdmin()): ?>
                        | <a href="/admin/system-info.php" class="text-decoration-none">System Info</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <!-- Auto-save functionality for forms -->
    <script>
    // Auto-save form data every 30 seconds
    let autoSaveInterval;
    
    function initAutoSave() {
        const forms = document.querySelectorAll('form[data-autosave]');
        
        forms.forEach(form => {
            const formId = form.getAttribute('data-autosave');
            
            // Load saved data on page load
            loadFormData(form, formId);
            
            // Save data on input change
            form.addEventListener('input', () => {
                saveFormData(form, formId);
            });
            
            // Clear saved data on successful submit
            form.addEventListener('submit', () => {
                clearFormData(formId);
            });
        });
    }
    
    function saveFormData(form, formId) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'csrf_token') { // Don't save CSRF token
                data[key] = value;
            }
        }
        
        localStorage.setItem(`form_${formId}`, JSON.stringify(data));
        
        // Show auto-save indicator
        showAutoSaveIndicator();
    }
    
    function loadFormData(form, formId) {
        const savedData = localStorage.getItem(`form_${formId}`);
        
        if (savedData) {
            const data = JSON.parse(savedData);
            
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
            
            // Show restore indicator
            showRestoreIndicator();
        }
    }
    
    function clearFormData(formId) {
        localStorage.removeItem(`form_${formId}`);
    }
    
    function showAutoSaveIndicator() {
        const indicator = document.getElementById('autosave-indicator');
        if (indicator) {
            indicator.textContent = 'Draft saved';
            indicator.className = 'badge bg-success';
            setTimeout(() => {
                indicator.textContent = '';
                indicator.className = '';
            }, 2000);
        }
    }
    
    function showRestoreIndicator() {
        const indicator = document.getElementById('autosave-indicator');
        if (indicator) {
            indicator.innerHTML = '<i class="fas fa-info-circle"></i> Draft restored';
            indicator.className = 'badge bg-info';
        }
    }
    
    // Initialize auto-save when DOM is loaded
    document.addEventListener('DOMContentLoaded', initAutoSave);
    </script>
    
    <!-- Form validation -->
    <script>
    // Bootstrap form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    </script>
    
    <!-- Rating system -->
    <script>
    function initRatingSystem() {
        const ratingInputs = document.querySelectorAll('.rating-input');
        
        ratingInputs.forEach(input => {
            const container = input.closest('.rating-container');
            const stars = container.querySelectorAll('.rating-star');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const rating = index + 1;
                    input.value = rating;
                    updateStarDisplay(stars, rating);
                    
                    // Trigger change event for auto-save
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
                
                star.addEventListener('mouseover', () => {
                    updateStarDisplay(stars, index + 1, true);
                });
            });
            
            container.addEventListener('mouseleave', () => {
                updateStarDisplay(stars, input.value);
            });
            
            // Initialize display
            updateStarDisplay(stars, input.value);
        });
    }
    
    function updateStarDisplay(stars, rating, isHover = false) {
        stars.forEach((star, index) => {
            star.classList.remove('active', 'hover');
            if (index < rating) {
                star.classList.add(isHover ? 'hover' : 'active');
            }
        });
    }
    
    // Initialize rating system when DOM is loaded
    document.addEventListener('DOMContentLoaded', initRatingSystem);
    </script>
    
    <!-- Confirmation dialogs -->
    <script>
    function confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
    
    // Add confirmation to delete buttons
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
                
                if (confirm(message)) {
                    // If it's a form button, submit the form
                    if (this.form) {
                        this.form.submit();
                    } else {
                        // If it's a link, follow the link
                        window.location.href = this.href;
                    }
                }
            });
        });
    });
    </script>
    
    <!-- Custom JavaScript for specific pages -->
    <?php if (isset($customJS)): ?>
    <script><?php echo $customJS; ?></script>
    <?php endif; ?>
    
    <!-- Page-specific JavaScript files -->
    <?php if (isset($jsFiles) && is_array($jsFiles)): ?>
        <?php foreach ($jsFiles as $jsFile): ?>
        <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>