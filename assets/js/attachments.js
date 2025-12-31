/**
 * LounGenie Portal - Attachment Handler JavaScript
 * Handles file uploads and attachment management
 * 
 * @package LounGenie Portal
 * @version 1.7.0
 */

(function ($) {
    'use strict';

    var LGPAttachments = {

        /**
         * Initialize attachment handlers
         */
        init: function () {
            this.bindEvents();
            this.setupDragDrop();
            this.setupLogoHoverEffects();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            $(document).on('click', '.lgp-attachment-zone', this.triggerFileSelect);
            $(document).on('change', '.lgp-attachment-input', this.handleFileSelect);
            $(document).on('click', '.lgp-attachment-remove', this.removeAttachment);
        },

        /**
         * Setup drag and drop
         */
        setupDragDrop: function () {
            var self = this;

            $(document).on('dragover', '.lgp-attachment-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $(document).on('dragleave', '.lgp-attachment-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            $(document).on('drop', '.lgp-attachment-zone', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');

                var files = e.originalEvent.dataTransfer.files;
                self.uploadFiles(files, $(this));
            });
        },

        /**
         * Trigger file input
         */
        triggerFileSelect: function () {
            $(this).find('.lgp-attachment-input').click();
        },

        /**
         * Handle file selection
         */
        handleFileSelect: function (e) {
            var files = e.target.files;
            var $zone = $(e.target).closest('.lgp-attachment-zone');

            LGPAttachments.uploadFiles(files, $zone);
        },

        /**
         * Upload files via AJAX
         */
        uploadFiles: function (files, $zone) {
            var self = this;

            for (var i = 0; i < files.length; i++) {
                var file = files[i];

                // Validate file before upload
                if (!self.validateFile(file, $zone)) {
                    continue;
                }

                self.uploadFile(file, $zone);
            }
        },

        /**
         * Validate file
         */
        validateFile: function (file, $zone) {
            var maxSize = lgpAttachments.maxFileSize;
            var allowedTypes = lgpAttachments.allowedTypes;
            var fileExt = file.name.split('.').pop().toLowerCase();

            if (file.size > maxSize) {
                this.showError(
                    $zone,
                    'File size exceeds maximum of ' + (maxSize / (1024 * 1024)) + ' MB'
                );
                return false;
            }

            if (allowedTypes.indexOf(fileExt) === -1) {
                this.showError(
                    $zone,
                    'File type not allowed. Allowed types: ' + allowedTypes.join(', ')
                );
                return false;
            }

            return true;
        },

        /**
         * Upload single file
         */
        uploadFile: function (file, $zone) {
            var self = this;
            var formData = new FormData();

            formData.append('action', 'lgp_upload_attachment');
            formData.append('nonce', lgpAttachments.nonce);
            formData.append('file', file);

            $.ajax({
                url: lgpAttachments.ajaxUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    self.showUploading($zone, file.name);
                },
                success: function (response) {
                    if (response.success) {
                        self.addAttachmentToList($zone, response.data);
                        self.showSuccess($zone, file.name + ' uploaded successfully');
                    } else {
                        self.showError($zone, response.data.message);
                    }
                },
                error: function () {
                    self.showError($zone, 'Failed to upload ' + file.name);
                }
            });
        },

        /**
         * Add attachment to list
         */
        addAttachmentToList: function ($zone, data) {
            var $list = $zone.find('.lgp-attachment-list');

            if ($list.length === 0) {
                $list = $('<ul class="lgp-attachment-list"></ul>');
                $zone.append($list);
            }

            var $item = $('<li class="lgp-attachment-item"></li>');
            var fileIcon = this.getFileIcon(data.file_name);
            var fileSize = this.formatFileSize(data.file_size);

            $item.html(
                '<div class="lgp-attachment-name">' +
                '<span class="lgp-attachment-icon">' + fileIcon + '</span>' +
                '<span>' + data.file_name + '</span>' +
                '</div>' +
                '<div>' +
                '<span class="lgp-attachment-size">' + fileSize + '</span>' +
                '<button type="button" class="lgp-attachment-remove" data-file-id="' + data.file_id + '" title="Remove">×</button>' +
                '</div>'
            );

            // Add hidden input to form
            var $form = $zone.closest('form');
            if ($form.length) {
                var $hidden = $('<input>')
                    .attr('type', 'hidden')
                    .attr('name', 'lgp_attachment_ids[]')
                    .val(data.file_id);
                $form.append($hidden);
            }

            $list.append($item);

            // Trigger custom event
            $(document).trigger('lgp-attachment-added', [data]);
        },

        /**
         * Remove attachment
         */
        removeAttachment: function (e) {
            e.preventDefault();

            var $btn = $(this);
            var fileId = $btn.data('file-id');
            var $item = $btn.closest('.lgp-attachment-item');

            $item.fadeOut(function () {
                $(this).remove();
            });

            // Remove hidden input
            $('input[name="lgp_attachment_ids[]"][value="' + fileId + '"]').remove();

            // Trigger custom event
            $(document).trigger('lgp-attachment-removed', [fileId]);
        },

        /**
         * Show upload status
         */
        showUploading: function ($zone, fileName) {
            var $message = $zone.find('.lgp-upload-status');

            if ($message.length === 0) {
                $message = $('<div class="lgp-upload-status" style="margin-top: 12px; padding: 12px; border-radius: 6px; background: rgba(58, 166, 185, 0.1); color: #3AA6B9;"></div>');
                $zone.append($message);
            }

            $message.html('Uploading ' + fileName + '... <span class="lgp-spinner">⟳</span>');
        },

        /**
         * Show success message
         */
        showSuccess: function ($zone, message) {
            var $message = $zone.find('.lgp-upload-status');

            if ($message.length === 0) {
                $message = $('<div class="lgp-upload-status" style="margin-top: 12px; padding: 12px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #10B981;"></div>');
                $zone.append($message);
            }

            $message.html('✓ ' + message);

            setTimeout(function () {
                $message.fadeOut(function () {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Show error message
         */
        showError: function ($zone, message) {
            var $message = $zone.find('.lgp-upload-status');

            if ($message.length === 0) {
                $message = $('<div class="lgp-upload-status" style="margin-top: 12px; padding: 12px; border-radius: 6px; background: rgba(220, 38, 38, 0.1); color: #DC2626;"></div>');
                $zone.append($message);
            }

            $message.html('✗ ' + message);

            setTimeout(function () {
                $message.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Get file icon based on extension
         */
        getFileIcon: function (fileName) {
            var ext = fileName.split('.').pop().toLowerCase();

            var icons = {
                pdf: '📄',
                doc: '📝',
                docx: '📝',
                xls: '📊',
                xlsx: '📊',
                ppt: '🎨',
                pptx: '🎨',
                txt: '📄',
                csv: '📊',
                jpg: '🖼️',
                jpeg: '🖼️',
                png: '🖼️',
                gif: '🖼️',
                zip: '📦',
            };

            return icons[ext] || '📎';
        },

        /**
         * Format file size
         */
        formatFileSize: function (bytes) {
            if (bytes === 0) return '0 Bytes';

            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Setup logo hover effects
         */
        setupLogoHoverEffects: function () {
            var $logo = $('.lgp-logo');

            $logo.on('mouseenter', function () {
                $(this).css({
                    'transform': 'scale(1.02)',
                    'transition': 'transform 150ms cubic-bezier(0.4, 0, 0.2, 1)'
                });
            });

            $logo.on('mouseleave', function () {
                $(this).css({
                    'transform': 'scale(1)',
                    'transition': 'transform 150ms cubic-bezier(0.4, 0, 0.2, 1)'
                });
            });
        }
    };

    /**
     * Initialize when DOM is ready
     */
    $(function () {
        LGPAttachments.init();

        // Add CSS for spinner animation
        if (!$('#lgp-spinner-css').length) {
            $('<style id="lgp-spinner-css">').text(
                '@keyframes lgpSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }' +
                '.lgp-spinner { display: inline-block; animation: lgpSpin 1s linear infinite; }'
            ).appendTo('head');
        }
    });

})(jQuery);
