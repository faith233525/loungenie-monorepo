/**
 * Support Ticket Form Handler
 * Handles form submission, validation, and file uploads
 */

(function (document, window) {
    'use strict';

    const FORM_CONFIG = {
        maxFileSize: 10 * 1024 * 1024, // 10MB
        maxFiles: 5,
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'application/zip'],
        fileExtensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']
    };

    class SupportTicketForm {
        constructor() {
            this.form = document.getElementById('lgp-support-ticket-form');
            if (!this.form) return;

            this.selectedFiles = [];
            this.init();
        }

        init() {
            this.attachEventListeners();
            this.setupCharacterCounters();
            this.setupFileUpload();
            this.setupFormValidation();
        }

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Form submission
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));

            // Character counters
            const subjectInput = this.form.querySelector('#lgp-subject');
            const descriptionInput = this.form.querySelector('#lgp-description');

            if (subjectInput) {
                subjectInput.addEventListener('input', () => this.updateCharacterCount('lgp-subject'));
            }

            if (descriptionInput) {
                descriptionInput.addEventListener('input', () => this.updateCharacterCount('lgp-description'));
            }

            // Company selector change (reload units)
            const companySelect = this.form.querySelector('#lgp-company');
            if (companySelect) {
                companySelect.addEventListener('change', () => this.loadUnitsForCompany(companySelect.value));
            }

            // Units affected radio button change
            const unitsAffectedRadios = this.form.querySelectorAll('input[name="units_affected"]');
            unitsAffectedRadios.forEach(radio => {
                radio.addEventListener('change', () => this.toggleUnitsSelection());
            });
        }

        /**
         * Setup character counter for text inputs
         */
        updateCharacterCount(fieldId) {
            const input = this.form.querySelector(`#${fieldId}`);
            const counter = this.form.querySelector(`#${fieldId}-length`);

            if (input && counter) {
                const length = input.value.length;
                const max = input.maxLength;

                counter.textContent = length;

                // Change counter color based on usage
                const counterElement = input.parentElement.querySelector('.lgp-form-counter');
                if (counterElement) {
                    if (length >= max * 0.9) {
                        counterElement.classList.add('warning');
                    } else {
                        counterElement.classList.remove('warning');
                    }
                }
            }
        }

        setupCharacterCounters() {
            ['lgp-subject', 'lgp-description'].forEach(fieldId => {
                this.updateCharacterCount(fieldId);
            });
        }

        /**
         * Setup file upload handling
         */
        setupFileUpload() {
            const fileInput = this.form.querySelector('#lgp-attachments');
            const uploadZone = this.form.querySelector('#lgp-file-upload-zone');

            if (!fileInput || !uploadZone) return;

            // Click to upload
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

            // Drag and drop
            uploadZone.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            uploadZone.addEventListener('drop', (e) => this.handleDrop(e));
        }

        handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const uploadZone = this.form.querySelector('#lgp-file-upload-zone');
            uploadZone.classList.add('drag-over');
        }

        handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            const uploadZone = this.form.querySelector('#lgp-file-upload-zone');
            uploadZone.classList.remove('drag-over');
        }

        handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            const uploadZone = this.form.querySelector('#lgp-file-upload-zone');
            uploadZone.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            this.processFiles(files);
        }

        handleFileSelect(e) {
            const files = e.target.files;
            this.processFiles(files);
        }

        processFiles(files) {
            const errors = [];

            for (let file of files) {
                // Validate file
                if (!this.validateFile(file, errors)) {
                    continue;
                }

                // Check if file already selected
                if (this.selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    errors.push(`${file.name} is already selected.`);
                    continue;
                }

                // Check file count
                if (this.selectedFiles.length >= FORM_CONFIG.maxFiles) {
                    errors.push(`Maximum ${FORM_CONFIG.maxFiles} files allowed.`);
                    break;
                }

                this.selectedFiles.push(file);
            }

            // Display errors
            this.displayFileErrors(errors);

            // Update file list display
            this.updateFileList();

            // Reset file input
            const fileInput = this.form.querySelector('#lgp-attachments');
            fileInput.value = '';
        }

        validateFile(file, errors) {
            // Check file size
            if (file.size > FORM_CONFIG.maxFileSize) {
                errors.push(`${file.name} exceeds maximum size of 10MB.`);
                return false;
            }

            // Check file type
            if (!FORM_CONFIG.allowedTypes.includes(file.type)) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!FORM_CONFIG.fileExtensions.includes(ext)) {
                    errors.push(`${file.name} has unsupported file type. Allowed: ${FORM_CONFIG.fileExtensions.join(', ')}`);
                    return false;
                }
            }

            return true;
        }

        displayFileErrors(errors) {
            const errorContainer = this.form.querySelector('#lgp-attachments-error');
            if (errors.length > 0) {
                errorContainer.innerHTML = errors.map(error => `<div class="lgp-form-error show">${this.escapeHtml(error)}</div>`).join('');
            } else {
                errorContainer.innerHTML = '';
            }
        }

        updateFileList() {
            const fileListContainer = this.form.querySelector('#lgp-file-list');
            const fileListItems = this.form.querySelector('#lgp-file-list-items');
            const fileCount = this.form.querySelector('#lgp-file-count');

            if (this.selectedFiles.length === 0) {
                fileListContainer.style.display = 'none';
                return;
            }

            // Build file list HTML
            const filesHtml = this.selectedFiles.map((file, index) => `
				<li>
					<div class="lgp-file-name">
						<i class="fa-solid fa-file"></i>
						<span>${this.escapeHtml(file.name)}</span>
					</div>
					<span class="lgp-file-size">${this.formatFileSize(file.size)}</span>
					<button type="button" class="lgp-file-remove" data-index="${index}">
						Remove
					</button>
				</li>
			`).join('');

            fileListItems.innerHTML = filesHtml;
            fileCount.textContent = `${this.selectedFiles.length} file${this.selectedFiles.length !== 1 ? 's' : ''} selected`;

            // Attach remove handlers
            fileListItems.querySelectorAll('.lgp-file-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const index = parseInt(btn.dataset.index);
                    this.selectedFiles.splice(index, 1);
                    this.updateFileList();
                    this.displayFileErrors([]);
                });
            });

            fileListContainer.style.display = 'block';
        }

        /**
         * Setup form validation
         */
        setupFormValidation() {
            // Set up real-time validation for required fields
            const inputs = this.form.querySelectorAll('[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('change', () => this.validateField(input));
            });
        }

        validateField(field) {
            const fieldName = field.getAttribute('name');
            const errorContainer = this.form.querySelector(`#${field.id}-error`);
            let isValid = true;
            let errorMessage = '';

            // Common validation
            if (field.hasAttribute('required') && !field.value.trim()) {
                isValid = false;
                errorMessage = 'This field is required.';
            }

            // Field-specific validation
            if (isValid && field.type === 'email') {
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value);
                if (!isValid) {
                    errorMessage = 'Please enter a valid email address.';
                }
            }

            if (isValid && field.type === 'tel' && field.value) {
                isValid = /^[\d\s()+-]{10,}$/.test(field.value.replace(/\s/g, ''));
                if (!isValid) {
                    errorMessage = 'Please enter a valid phone number.';
                }
            }

            if (isValid && field.minLength && field.value.length < field.minLength) {
                isValid = false;
                errorMessage = `Minimum ${field.minLength} characters required.`;
            }

            if (isValid && fieldName === 'first_name' || fieldName === 'last_name') {
                isValid = /^[a-zA-Z\s'-]{2,}$/.test(field.value);
                if (!isValid) {
                    errorMessage = 'Please enter a valid name (letters, spaces, hyphens, or apostrophes only).';
                }
            }

            // Update error display
            if (errorContainer) {
                if (!isValid) {
                    errorContainer.textContent = errorMessage;
                    errorContainer.classList.add('show');
                } else {
                    errorContainer.textContent = '';
                    errorContainer.classList.remove('show');
                }
            }

            return isValid;
        }

        toggleUnitsSelection() {
            const unitsSelection = this.form.querySelector('#lgp-units-list');
            if (!unitsSelection) return;

            const selectedOption = this.form.querySelector('input[name="units_affected"]:checked');
            if (selectedOption && selectedOption.value === '1') {
                unitsSelection.disabled = true;
                unitsSelection.value = [];
            } else {
                unitsSelection.disabled = false;
            }
        }

        loadUnitsForCompany(companyId) {
            if (!companyId) return;

            // This would typically be an AJAX call to load units for the selected company
            // For now, we'll just clear the selection
            const unitsSelect = this.form.querySelector('#lgp-units-list');
            if (unitsSelect) {
                unitsSelect.value = [];
            }
        }

        /**
         * Handle form submission
         */
        async handleSubmit(e) {
            e.preventDefault();

            // Validate all fields
            if (!this.validateForm()) {
                return;
            }

            // Show loading state
            const submitBtn = this.form.querySelector('#lgp-submit-btn');
            const btnText = submitBtn.querySelector('.lgp-btn-text');
            const btnSpinner = submitBtn.querySelector('.lgp-btn-spinner');

            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-flex';

            try {
                // Create FormData object
                const formData = new FormData(this.form);

                // Add selected files to FormData
                if (this.selectedFiles.length > 0) {
                    // Clear existing attachments field
                    formData.delete('attachments[]');

                    // Add selected files
                    this.selectedFiles.forEach(file => {
                        formData.append('attachments[]', file);
                    });
                }

                // Submit form via AJAX
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    this.handleSuccess(result.data);
                } else {
                    this.handleError(result.data?.message || 'An error occurred. Please try again.');
                }
            } catch (error) {
                this.handleError('Network error. Please try again.');
                console.error('Form submission error:', error);
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        }

        validateForm() {
            let isValid = true;

            // Validate all required fields
            const inputs = this.form.querySelectorAll('[required]');
            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            // Validate checkboxes
            const consentContactCheckbox = this.form.querySelector('input[name="consent_contact"]');
            const consentPrivacyCheckbox = this.form.querySelector('input[name="consent_privacy"]');

            if (!consentContactCheckbox.checked) {
                isValid = false;
                const errorContainer = this.form.querySelector('#lgp-consent-contact-error');
                if (errorContainer) {
                    errorContainer.textContent = 'You must agree to be contacted.';
                    errorContainer.classList.add('show');
                }
            }

            if (!consentPrivacyCheckbox.checked) {
                isValid = false;
                const errorContainer = this.form.querySelector('#lgp-consent-privacy-error');
                if (errorContainer) {
                    errorContainer.textContent = 'You must agree to the privacy policy.';
                    errorContainer.classList.add('show');
                }
            }

            return isValid;
        }

        handleSuccess(data) {
            const successMessage = this.form.querySelector('#lgp-success-message');
            const ticketId = this.form.querySelector('#lgp-success-ticket-id');

            if (successMessage && ticketId) {
                ticketId.textContent = `Your ticket number is: ${this.escapeHtml(data.ticket_id)}`;
                successMessage.style.display = 'block';
                successMessage.scrollIntoView({ behavior: 'smooth' });

                // Reset form
                this.form.reset();
                this.selectedFiles = [];
                this.updateFileList();

                // Hide form after success
                setTimeout(() => {
                    this.form.style.display = 'none';
                }, 3000);
            }
        }

        handleError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'lgp-alert lgp-alert-error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.innerHTML = `<div class="lgp-alert-content"><h4>Error</h4><p>${this.escapeHtml(message)}</p></div>`;

            this.form.insertBefore(errorDiv, this.form.firstChild);

            // Auto-remove error after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        /**
         * Utility functions
         */
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize form when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        new SupportTicketForm();
    });

})();
