/**
 * LounGenie Portal - Attachment Uploader
 * Drag-and-drop file upload with validation
 * v1.7.0
 */

const AttachmentUploader = {
    config: {
        maxFileSize: 10 * 1024 * 1024, // 10 MB
        allowedExtensions: [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'zip'
        ],
        allowedMimes: [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip'
        ]
    },

    attachments: [],

    /**
     * Initialize attachment uploader
     */
    init() {
        this.zone = document.querySelector('.lgp-attachment-zone');
        this.input = document.querySelector('input[name="attachments"]');
        this.list = document.querySelector('.lgp-attachment-list');

        if (!this.zone || !this.input) return;

        // Drag events
        this.zone.addEventListener('dragover', (e) => this.onDragOver(e));
        this.zone.addEventListener('dragleave', (e) => this.onDragLeave(e));
        this.zone.addEventListener('drop', (e) => this.onDrop(e));

        // Click to upload
        this.zone.addEventListener('click', () => this.input.click());

        // File input change
        this.input.addEventListener('change', (e) => this.handleFiles(e.target.files));

        console.log('✓ AttachmentUploader initialized');
    },

    /**
     * Handle drag over
     */
    onDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.zone.classList.add('drag-over');
    },

    /**
     * Handle drag leave
     */
    onDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.zone.classList.remove('drag-over');
    },

    /**
     * Handle drop
     */
    onDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.zone.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        this.handleFiles(files);
    },

    /**
     * Handle file selection
     */
    handleFiles(files) {
        const validFiles = [];

        for (let file of files) {
            const validation = this.validateFile(file);

            if (validation.valid) {
                validFiles.push(file);
            } else {
                this.showError(file.name, validation.error);
            }
        }

        if (validFiles.length > 0) {
            validFiles.forEach(file => this.uploadFile(file));
        }
    },

    /**
     * Validate file
     */
    validateFile(file) {
        // Check file size
        if (file.size > this.config.maxFileSize) {
            return {
                valid: false,
                error: `File exceeds ${this.formatBytes(this.config.maxFileSize)} limit`
            };
        }

        // Check extension
        const ext = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedExtensions.includes(ext)) {
            return {
                valid: false,
                error: 'File type not allowed. Allowed: ' + this.config.allowedExtensions.join(', ')
            };
        }

        // Check MIME type
        if (!this.config.allowedMimes.includes(file.type)) {
            console.warn(`Warning: ${file.name} has unexpected MIME type: ${file.type}`);
            // Still allow - might be valid
        }

        return { valid: true };
    },

    /**
     * Upload file
     */
    uploadFile(file) {
        const formData = new FormData();
        formData.append('action', 'lgp_upload_attachment');
        formData.append('nonce', window.lgpNonce);
        formData.append('file', file);

        // Get ticket ID if available
        const ticketIdInput = document.querySelector('input[name="ticket_id"]');
        if (ticketIdInput) {
            formData.append('ticket_id', ticketIdInput.value);
        }

        // Show progress
        const progressItem = this.createProgressItem(file.name);

        fetch(window.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayAttachment(data.data.attachment, progressItem);
                    this.attachments.push(data.data.attachment);
                    console.log('✓ File uploaded:', file.name);
                } else {
                    this.showError(file.name, data.data.message || 'Upload failed');
                    progressItem.remove();
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                this.showError(file.name, error.message);
                progressItem.remove();
            });
    },

    /**
     * Create progress item
     */
    createProgressItem(filename) {
        const item = document.createElement('li');
        item.className = 'lgp-attachment-item lgp-attachment-uploading';
        item.innerHTML = `
            <div class="lgp-attachment-name">
                <span>⏳</span>
                <span>${filename}</span>
            </div>
            <div style="font-size: 12px; color: var(--lgp-color-text-tertiary);">Uploading...</div>
        `;

        if (this.list) {
            this.list.appendChild(item);
        }

        return item;
    },

    /**
     * Display attachment
     */
    displayAttachment(attachment, progressItem) {
        const item = document.createElement('li');
        item.className = 'lgp-attachment-item';
        item.dataset.id = attachment.id;

        const icon = this.getFileIcon(attachment.mime);
        const size = this.formatBytes(attachment.size);

        item.innerHTML = `
            <div class="lgp-attachment-name">
                <span>${icon}</span>
                <a href="${attachment.url}" target="_blank" style="color: var(--lgp-color-brand); text-decoration: none;">
                    ${attachment.name}
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="lgp-attachment-size">${size}</span>
                <button type="button" class="lgp-attachment-delete" 
                        data-id="${attachment.id}" 
                        style="background: none; border: none; color: #DC2626; cursor: pointer; font-size: 18px; padding: 4px; transition: all 150ms;">
                    ×
                </button>
            </div>
        `;

        // Delete button
        const deleteBtn = item.querySelector('.lgp-attachment-delete');
        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.removeAttachment(attachment.id, item);
        });

        deleteBtn.addEventListener('mouseover', () => {
            deleteBtn.style.transform = 'scale(1.2)';
        });

        deleteBtn.addEventListener('mouseout', () => {
            deleteBtn.style.transform = 'scale(1)';
        });

        if (progressItem) {
            progressItem.replaceWith(item);
        } else if (this.list) {
            this.list.appendChild(item);
        }
    },

    /**
     * Remove attachment
     */
    removeAttachment(id, element) {
        // Confirm deletion
        if (!confirm('Remove this attachment?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'lgp_delete_attachment');
        formData.append('nonce', window.lgpNonce);
        formData.append('id', id);

        fetch(window.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.style.animation = 'fadeOut 300ms ease-out forwards';
                    setTimeout(() => element.remove(), 300);

                    // Remove from local array
                    this.attachments = this.attachments.filter(a => a.id !== id);
                    console.log('✓ Attachment removed:', id);
                } else {
                    alert('Failed to remove attachment: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Error removing attachment');
            });
    },

    /**
     * Get file icon by MIME type
     */
    getFileIcon(mime) {
        if (!mime) return '📎';

        if (mime.includes('pdf')) return '📄';
        if (mime.includes('word') || mime.includes('document')) return '📝';
        if (mime.includes('sheet') || mime.includes('excel')) return '📊';
        if (mime.includes('powerpoint') || mime.includes('presentation')) return '🎯';
        if (mime.includes('image')) return '🖼️';
        if (mime.includes('zip') || mime.includes('compressed')) return '🗂️';
        if (mime.includes('text')) return '📃';

        return '📎';
    },

    /**
     * Format bytes to human readable
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    /**
     * Show error message
     */
    showError(filename, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'lgp-attachment-error';
        errorDiv.style.cssText = `
            padding: 12px;
            margin-bottom: 12px;
            background: #FEE2E2;
            border-left: 3px solid #DC2626;
            border-radius: 4px;
            color: #991B1B;
            font-size: 13px;
        `;
        errorDiv.innerHTML = `<strong>${filename}:</strong> ${message}`;

        if (this.zone) {
            this.zone.parentNode.insertBefore(errorDiv, this.zone);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                errorDiv.style.animation = 'fadeOut 300ms ease-out forwards';
                setTimeout(() => errorDiv.remove(), 300);
            }, 5000);
        }
    }
};

// Auto-initialize when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        AttachmentUploader.init();
    });
} else {
    AttachmentUploader.init();
}
