/**
 * Admin Credentials Manager JavaScript
 * Handles partner credentials CRUD operations
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('lgp-credentials-container');

    // Load companies on page load
    loadCompanies();

    function loadCompanies() {
        jQuery.ajax({
            url: lgpCredentials.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lgp_get_companies',
                nonce: lgpCredentials.nonce,
            },
            success: function (response) {
                if (response.success) {
                    renderCompaniesTable(response.data);
                }
            },
            error: function () {
                container.innerHTML = '<div class="notice notice-error"><p>' + 'Failed to load companies' + '</p></div>';
            }
        });
    }

    function renderCompaniesTable(companies) {
        let html = '<table class="wp-list-table widefat striped hover">';
        html += '<thead><tr>';
        html += '<th>Company Name</th>';
        html += '<th>Username</th>';
        html += '<th>Primary Contact</th>';
        html += '<th>Status</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        companies.forEach(function (company) {
            const status = company.status === 'configured'
                ? '<span style="color: green; font-weight: bold;">✓ Configured</span>'
                : '<span style="color: orange; font-weight: bold;">⚠ Pending</span>';

            html += '<tr>';
            html += '<td>' + escapeHtml(company.name) + '</td>';
            html += '<td>' + (company.partner_username ? escapeHtml(company.partner_username) : '—') + '</td>';
            html += '<td>' + (company.primary_contact_name ? escapeHtml(company.primary_contact_name) : '—') + '</td>';
            html += '<td>' + status + '</td>';
            html += '<td>';
            html += '<button class="button button-primary" onclick="editCredentials(' + company.id + ')">' + 'Edit' + '</button> ';
            html += '<button class="button" onclick="viewCredentials(' + company.id + ')">' + 'View' + '</button>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    window.editCredentials = function (companyId) {
        const company = findCompanyById(companyId);
        if (!company) return;

        // Create modal form
        const modal = createModal(company);
        document.body.appendChild(modal);
    };

    window.viewCredentials = function (companyId) {
        const company = findCompanyById(companyId);
        if (!company) return;

        let info = '<strong>Company:</strong> ' + escapeHtml(company.name) + '<br>';
        info += '<strong>Username:</strong> ' + (company.partner_username ? escapeHtml(company.partner_username) : 'Not set') + '<br>';
        info += '<strong>Primary Contact:</strong> ' + (company.primary_contact_name ? escapeHtml(company.primary_contact_name) : 'Not set') + '<br>';
        info += '<strong>Primary Email:</strong> ' + (company.primary_contact_email ? '<a href="mailto:' + escapeHtml(company.primary_contact_email) + '">' + escapeHtml(company.primary_contact_email) + '</a>' : 'Not set') + '<br>';
        info += '<strong>Primary Phone:</strong> ' + (company.primary_contact_phone ? escapeHtml(company.primary_contact_phone) : 'Not set') + '<br>';
        info += '<strong>Secondary Contact:</strong> ' + (company.secondary_contact_name ? escapeHtml(company.secondary_contact_name) : 'Not set') + '<br>';

        alert(info);
    };

    function findCompanyById(id) {
        // This would need companies data available globally or fetched again
        // For now, we'll just return minimal object
        return { id: id };
    }

    function createModal(company) {
        const modal = document.createElement('div');
        modal.className = 'lgp-modal';
        modal.id = 'lgp-credentials-modal-' + company.id;
        modal.innerHTML = `
			<div class="lgp-modal-overlay" onclick="closeModal('lgp-credentials-modal-${company.id}')"></div>
			<div class="lgp-modal-content">
				<button class="lgp-modal-close" onclick="closeModal('lgp-credentials-modal-${company.id}')">&times;</button>
				<h2>Configure Partner Credentials</h2>
				<p><strong>Company:</strong> ${escapeHtml(company.name)}</p>
				
				<form id="lgp-credentials-form-${company.id}" onsubmit="submitCredentials(event, ${company.id})">
					<div class="lgp-form-group">
						<label for="username-${company.id}">Partner Username *</label>
						<input type="text" id="username-${company.id}" name="partner_username" required 
							   placeholder="Enter unique username for this company" />
					</div>

					<div class="lgp-form-group">
						<label for="password-${company.id}">Partner Password *</label>
						<input type="password" id="password-${company.id}" name="partner_password" required 
							   placeholder="Enter strong password" />
					</div>

					<h3>Primary Contact *</h3>
					<div class="lgp-form-group">
						<label for="primary-name-${company.id}">Name *</label>
						<input type="text" id="primary-name-${company.id}" name="primary_contact_name" required />
					</div>

					<div class="lgp-form-group">
						<label for="primary-email-${company.id}">Email *</label>
						<input type="email" id="primary-email-${company.id}" name="primary_contact_email" required />
					</div>

					<div class="lgp-form-group">
						<label for="primary-phone-${company.id}">Phone</label>
						<input type="tel" id="primary-phone-${company.id}" name="primary_contact_phone" />
					</div>

					<h3>Secondary Contact (Optional)</h3>
					<div class="lgp-form-group">
						<label for="secondary-name-${company.id}">Name</label>
						<input type="text" id="secondary-name-${company.id}" name="secondary_contact_name" />
					</div>

					<div class="lgp-form-group">
						<label for="secondary-email-${company.id}">Email</label>
						<input type="email" id="secondary-email-${company.id}" name="secondary_contact_email" />
					</div>

					<div class="lgp-form-group">
						<label for="secondary-phone-${company.id}">Phone</label>
						<input type="tel" id="secondary-phone-${company.id}" name="secondary_contact_phone" />
					</div>

					<div class="lgp-form-actions">
						<button type="submit" class="button button-primary">Save Credentials</button>
						<button type="button" class="button" onclick="closeModal('lgp-credentials-modal-${company.id}')">Cancel</button>
					</div>
				</form>
			</div>
		`;

        return modal;
    }

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.remove();
        }
    };

    window.submitCredentials = function (event, companyId) {
        event.preventDefault();

        const form = document.getElementById('lgp-credentials-form-' + companyId);
        const formData = new FormData(form);

        jQuery.ajax({
            url: lgpCredentials.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lgp_save_credentials',
                nonce: lgpCredentials.nonce,
                company_id: companyId,
                partner_username: formData.get('partner_username'),
                partner_password: formData.get('partner_password'),
                primary_contact_name: formData.get('primary_contact_name'),
                primary_contact_email: formData.get('primary_contact_email'),
                primary_contact_phone: formData.get('primary_contact_phone'),
                secondary_contact_name: formData.get('secondary_contact_name'),
                secondary_contact_email: formData.get('secondary_contact_email'),
                secondary_contact_phone: formData.get('secondary_contact_phone'),
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    closeModal('lgp-credentials-modal-' + companyId);
                    loadCompanies(); // Reload table
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function () {
                alert('Failed to save credentials');
            }
        });
    };

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
