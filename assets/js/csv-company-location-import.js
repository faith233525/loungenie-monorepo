/**
 * CSV Company + Location Import Handler
 * Handles file upload, validation, preview, and display of import results
 */

(function () {
    'use strict';

    const API_URL = lgpCSVCompanyLocation.apiURL;
    const PREVIEW_URL = lgpCSVCompanyLocation.previewURL;
    const NONCE = lgpCSVCompanyLocation.nonce;

    /**
     * Initialize on page load
     */
    document.addEventListener('DOMContentLoaded', function () {
        initFileInput();
        initFormSubmit();
        initSampleDownload();
    });

    /**
     * Initialize file input handler
     */
    function initFileInput() {
        const fileInput = document.getElementById('lgp-csv-file');
        const fileName = document.querySelector('.lgp-file-name');

        if (!fileInput) return;

        fileInput.addEventListener('change', function () {
            fileName.textContent = this.files.length ? this.files[0].name : 'No file chosen';
        });
    }

    /**
     * Initialize form submission
     */
    function initFormSubmit() {
        const form = document.getElementById('lgp-csv-upload-form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            handleFormSubmit();
        });
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit() {
        const fileInput = document.getElementById('lgp-csv-file');
        const dryRun = document.getElementById('lgp-dry-run').checked;
        const btn = document.getElementById('lgp-upload-btn');
        const spinner = document.querySelector('.spinner');

        if (!fileInput.files.length) {
            alert(lgpCSVCompanyLocation.i18n?.no_file || 'Please select a file');
            return;
        }

        // Disable button and show spinner
        btn.disabled = true;
        spinner.style.display = 'inline-block';

        try {
            // Show preview first
            const preview = await fetchPreview(fileInput.files[0]);
            if (preview.error) {
                alert(preview.error);
                btn.disabled = false;
                spinner.style.display = 'none';
                return;
            }

            // If not dry run, show preview and ask for confirmation
            if (!dryRun) {
                const confirmImport = confirm(
                    `Preview: ${preview.total_rows} rows found\n\n` +
                    `Sample companies:\n${preview.sample_rows.slice(0, 3).map(r => r.company_name).join('\n')}\n\n` +
                    'Click OK to import all rows.'
                );

                if (!confirmImport) {
                    btn.disabled = false;
                    spinner.style.display = 'none';
                    return;
                }
            }

            // Upload and process
            const results = await fetchUpload(fileInput.files[0], dryRun);
            displayResults(results);

        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            btn.disabled = false;
            spinner.style.display = 'none';
        }
    }

    /**
     * Fetch CSV preview
     */
    async function fetchPreview(file) {
        const formData = new FormData();
        formData.append('csv_file', file);

        try {
            const response = await fetch(PREVIEW_URL, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': NONCE,
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                return { error: data.message || 'Preview failed' };
            }

            return data;
        } catch (error) {
            return { error: error.message };
        }
    }

    /**
     * Fetch CSV upload and process
     */
    async function fetchUpload(file, dryRun) {
        const formData = new FormData();
        formData.append('csv_file', file);
        formData.append('dry_run', dryRun ? '1' : '0');

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': NONCE,
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Upload failed');
        }

        return data;
    }

    /**
     * Display import results
     */
    function displayResults(results) {
        const resultsDiv = document.getElementById('lgp-csv-results');
        const totalRows = document.getElementById('lgp-total-rows');
        const successCount = document.getElementById('lgp-success-count');
        const errorCount = document.getElementById('lgp-error-count');
        const errorDetails = document.getElementById('lgp-error-details');
        const errorList = document.getElementById('lgp-error-list');
        const successDetails = document.getElementById('lgp-success-details');
        const successList = document.getElementById('lgp-success-list');

        totalRows.textContent = results.total_rows;
        successCount.textContent = results.success.length;
        errorCount.textContent = results.errors.length;

        // Display errors
        if (results.errors.length > 0) {
            errorDetails.style.display = 'block';
            errorList.innerHTML = results.errors
                .map(
                    (err) =>
                        `<tr>
						<td>${err.row}</td>
						<td>${err.company}</td>
						<td>${err.error}</td>
					</tr>`
                )
                .join('');
        } else {
            errorDetails.style.display = 'none';
        }

        // Display successes
        if (results.success.length > 0) {
            successDetails.style.display = 'block';
            successList.innerHTML = results.success
                .map(
                    (item) =>
                        `<tr>
						<td>${item.company}</td>
						<td>${item.user_login}</td>
						<td>${item.city_state}</td>
						<td><a href="javascript:void(0)" class="button button-small">View</a></td>
					</tr>`
                )
                .join('');
        } else {
            successDetails.style.display = 'none';
        }

        // Show results section
        resultsDiv.style.display = 'block';

        // Scroll to results
        resultsDiv.scrollIntoView({ behavior: 'smooth' });

        // Show dry run notice
        if (results.dry_run) {
            const notice = document.createElement('div');
            notice.className = 'notice notice-info';
            notice.innerHTML =
                '<p><strong>Dry Run:</strong> No data was imported. Review the results above and uncheck "Dry Run" to import.</p>';
            resultsDiv.parentNode.insertBefore(notice, resultsDiv);
        }
    }

    /**
     * Initialize sample CSV download
     */
    function initSampleDownload() {
        const btn = document.getElementById('lgp-download-sample-csv');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            downloadSampleCSV();
        });
    }

    /**
     * Download sample CSV template
     */
    function downloadSampleCSV() {
        const headers = [
            'company_name',
            'user_login',
            'user_pass',
            'street_address',
            'city',
            'state',
            'zip',
            'country',
            'management_company',
            'units',
            'number',
            'top_colour',
            'lock',
            'master_code',
            'sub_master_code',
            'lock_part',
            'key',
        ];

        const sampleRows = [
            [
                'Beachfront Resort',
                'beach_user',
                'Pass123!',
                '123 Ocean Drive',
                'Miami',
                'FL',
                '33139',
                'USA',
                'Resort Management Co',
                '24',
                '1',
                '#0066CC',
                'Schlage',
                '1234',
                '5678',
                'Lock Core',
                'Key-A1',
            ],
            [
                'Mountain Lodge',
                'mountain_user',
                'Pass456!',
                '456 Mountain Road',
                'Denver',
                'CO',
                '80202',
                'USA',
                'Alpine Properties',
                '12',
                '2',
                '#663300',
                'Kwikset',
                '9012',
                '3456',
                'Lock Housing',
                'Key-B2',
            ],
        ];

        // Create CSV content
        const csv = [headers, ...sampleRows]
            .map((row) => row.map((cell) => `"${cell}"`).join(','))
            .join('\n');

        // Create blob and download
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'loungenie-portal-company-location-sample.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
})();
