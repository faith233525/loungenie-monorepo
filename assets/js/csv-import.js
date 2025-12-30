/**
 * CSV Partner Import - Frontend JavaScript
 * Handles file upload, validation, and results display
 *
 * @package LounGenie Portal
 */

(function ($) {
    'use strict';

    const CSVImport = {
        /**
         * Initialize
         */
        init: function () {
            this.bindEvents();
            this.setupFileInput();
            this.setupSampleDownload();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            $('#lgp-csv-upload-form').on('submit', this.handleFormSubmit.bind(this));
            $('#lgp-csv-file').on('change', this.handleFileSelect.bind(this));
        },

        /**
         * Setup file input display
         */
        setupFileInput: function () {
            const $fileInput = $('#lgp-csv-file');
            const $fileName = $('.lgp-file-name');

            $fileInput.on('change', function () {
                const fileName = this.files.length > 0 ? this.files[0].name : lgpCsvImport.translations.noFileChosen || 'No file chosen';
                $fileName.text(fileName);
            });
        },

        /**
         * Setup sample CSV download
         */
        setupSampleDownload: function () {
            $('#lgp-download-sample-csv').on('click', function (e) {
                e.preventDefault();

                // Generate sample CSV content
                const headers = [
                    'company_name',
                    'company_email',
                    'status',
                    'primary_contact_name',
                    'primary_contact_title',
                    'primary_contact_email',
                    'primary_contact_phone',
                    'secondary_contact_name',
                    'secondary_contact_title',
                    'secondary_contact_email',
                    'secondary_contact_phone'
                ];

                const sampleRows = [
                    [
                        'Acme Corporation',
                        'contact@acme.com',
                        'active',
                        'John Smith',
                        'Operations Manager',
                        'john.smith@acme.com',
                        '555-0100',
                        'Jane Doe',
                        'Assistant Manager',
                        'jane.doe@acme.com',
                        '555-0101'
                    ],
                    [
                        'Tech Solutions Inc',
                        'info@techsolutions.com',
                        'active',
                        'Mike Johnson',
                        'Director',
                        'mike.j@techsolutions.com',
                        '555-0200',
                        '',
                        '',
                        '',
                        ''
                    ]
                ];

                // Create CSV content
                let csvContent = 'data:text/csv;charset=utf-8,';
                csvContent += headers.join(',') + '\n';
                sampleRows.forEach(function (row) {
                    csvContent += row.map(field => `"${field}"`).join(',') + '\n';
                });

                // Trigger download
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', 'partner-import-sample.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        },

        /**
         * Handle file selection
         */
        handleFileSelect: function (e) {
            const file = e.target.files[0];

            if (!file) {
                return;
            }

            // Validate file size
            if (file.size > lgpCsvImport.maxFileSize) {
                this.showError(lgpCsvImport.translations.fileTooLarge);
                $('#lgp-csv-file').val('');
                $('.lgp-file-name').text(lgpCsvImport.translations.noFileChosen || 'No file chosen');
                return;
            }

            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                this.showError(lgpCsvImport.translations.invalidFileType);
                $('#lgp-csv-file').val('');
                $('.lgp-file-name').text(lgpCsvImport.translations.noFileChosen || 'No file chosen');
                return;
            }
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function (e) {
            e.preventDefault();

            const $form = $(e.target);
            const $submitBtn = $('#lgp-upload-btn');
            const $spinner = $form.find('.spinner');
            const fileInput = document.getElementById('lgp-csv-file');
            const dryRun = $('#lgp-dry-run').is(':checked');

            // Validate file selected
            if (!fileInput.files.length) {
                this.showError('Please select a CSV file');
                return;
            }

            const file = fileInput.files[0];

            // Disable submit button and show spinner
            $submitBtn.prop('disabled', true);
            $spinner.addClass('is-active');
            $submitBtn.text(lgpCsvImport.translations.uploading);

            // Hide previous results
            $('#lgp-csv-results').hide();

            // Prepare form data
            const formData = new FormData();
            formData.append('csv_file', file);
            if (dryRun) {
                formData.append('dry_run', '1');
            }

            // Upload and process
            $.ajax({
                url: lgpCsvImport.ajaxUrl + 'partners',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', lgpCsvImport.nonce);
                },
                success: this.handleUploadSuccess.bind(this),
                error: this.handleUploadError.bind(this),
                complete: function () {
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $submitBtn.text(dryRun ? 'Preview Import' : 'Upload and Process');
                }
            });
        },

        /**
         * Handle upload success
         */
        handleUploadSuccess: function (response) {
            console.log('Import results:', response);

            // Display results
            $('#lgp-csv-results').show();
            $('#lgp-total-rows').text(response.total || 0);
            $('#lgp-success-count').text(response.success || 0);
            $('#lgp-error-count').text(response.errors || 0);

            // Show dry run notice if applicable
            if (response.dry_run) {
                this.showNotice('This was a preview. No data was imported.', 'info');
            } else {
                this.showNotice('Import completed successfully!', 'success');
            }

            // Display success details
            if (response.imported && response.imported.length > 0) {
                this.displaySuccessDetails(response.imported, response.dry_run);
            }

            // Display error details
            if (response.failed && response.failed.length > 0) {
                this.displayErrorDetails(response.failed);
            }

            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#lgp-csv-results').offset().top - 100
            }, 500);

            // Clear form
            $('#lgp-csv-file').val('');
            $('.lgp-file-name').text('No file chosen');
            $('#lgp-dry-run').prop('checked', false);
        },

        /**
         * Handle upload error
         */
        handleUploadError: function (xhr) {
            let errorMsg = 'Upload failed. Please try again.';

            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    // Use default message
                }
            }

            this.showError(errorMsg);
        },

        /**
         * Display success details table
         */
        displaySuccessDetails: function (imported, isDryRun) {
            const $successList = $('#lgp-success-list');
            $successList.empty();

            imported.forEach(function (item) {
                const actionLabel = isDryRun
                    ? 'Would Import'
                    : (item.action === 'created' ? 'Created' : 'Updated');

                const row = `
					<tr>
						<td>${this.escapeHtml(item.name)}</td>
						<td>${this.escapeHtml(item.email)}</td>
						<td>
							<span class="lgp-status-badge lgp-status-${item.status}">
								${this.escapeHtml(item.status)}
							</span>
						</td>
						<td>
							${this.escapeHtml(item.primary_contact)}
							<span class="lgp-action-label">${actionLabel}</span>
						</td>
					</tr>
				`;

                $successList.append(row);
            }.bind(this));

            $('#lgp-success-details').show();
        },

        /**
         * Display error details table
         */
        displayErrorDetails: function (failed) {
            const $errorList = $('#lgp-error-list');
            $errorList.empty();

            failed.forEach(function (item) {
                const row = `
					<tr>
						<td><strong>Line ${item.line}</strong></td>
						<td>${this.escapeHtml(item.company || 'N/A')}</td>
						<td class="lgp-error-message">${this.escapeHtml(item.error)}</td>
					</tr>
				`;

                $errorList.append(row);
            }.bind(this));

            $('#lgp-error-details').show();
        },

        /**
         * Show error notice
         */
        showError: function (message) {
            this.showNotice(message, 'error');
        },

        /**
         * Show notice
         */
        showNotice: function (message, type) {
            type = type || 'info';

            const $notice = $('<div>')
                .addClass('notice notice-' + type + ' is-dismissible')
                .html('<p>' + this.escapeHtml(message) + '</p>');

            // Add dismiss button functionality
            $notice.on('click', '.notice-dismiss', function () {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            });

            // Insert at top of page
            $('.lgp-csv-import-page').prepend($notice);

            // Auto-dismiss after 5 seconds (except errors)
            if (type !== 'error') {
                setTimeout(function () {
                    $notice.fadeOut(300, function () {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    // Initialize when document ready
    $(document).ready(function () {
        if ($('.lgp-csv-import-page').length) {
            CSVImport.init();
        }
    });

})(jQuery);
