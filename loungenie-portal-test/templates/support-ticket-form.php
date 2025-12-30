<?php
/**
 * Support Ticket Form Template
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user data if logged in
$user_data = array();
if ( is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	$user_data    = array(
		'first_name' => $current_user->first_name,
		'last_name'  => $current_user->last_name,
		'email'      => $current_user->user_email,
		'company_id' => LGP_Auth::get_user_company_id(),
	);
}

// Generate nonce
$nonce            = wp_create_nonce( 'lgp_submit_support_ticket' );
$ticket_reference = 'TKT-' . date( 'YmdHis' ) . rand( 100, 999 );

?>
<div class="lgp-support-ticket-form-wrapper">
	<div class="form-messages"></div>

	<form id="lgp-support-ticket-form" class="lgp-support-ticket-form" method="POST" enctype="multipart/form-data">
		<!-- Form Header -->
		<div class="lgp-form-header">
			<h2><?php _e( 'Submit a Support Ticket', 'loungenie-portal' ); ?></h2>
			<p class="lgp-form-description">
				<?php _e( 'We\'re here to help! Please provide details about your issue and we\'ll get back to you as soon as possible.', 'loungenie-portal' ); ?>
			</p>
		</div>

		<!-- Hidden Fields -->
		<input type="hidden" name="lgp_ticket_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<input type="hidden" name="ticket_reference" value="<?php echo esc_attr( $ticket_reference ); ?>">

		<!-- Requester Information -->
		<fieldset>
			<legend class="lgp-form-legend"><?php _e( 'Your Information', 'loungenie-portal' ); ?></legend>

			<div class="lgp-form-row">
				<div class="lgp-form-group">
					<label for="lgp-first-name">
						<?php _e( 'First Name', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="text" 
						id="lgp-first-name" 
						name="first_name" 
						class="lgp-form-control"
						value="<?php echo esc_attr( $user_data['first_name'] ?? '' ); ?>"
						<?php echo is_user_logged_in() ? 'readonly' : ''; ?>
						required
					>
					<div class="error-message"></div>
				</div>

				<div class="lgp-form-group">
					<label for="lgp-last-name">
						<?php _e( 'Last Name', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="text" 
						id="lgp-last-name" 
						name="last_name" 
						class="lgp-form-control"
						value="<?php echo esc_attr( $user_data['last_name'] ?? '' ); ?>"
						<?php echo is_user_logged_in() ? 'readonly' : ''; ?>
						required
					>
					<div class="error-message"></div>
				</div>
			</div>

			<div class="lgp-form-row">
				<div class="lgp-form-group">
					<label for="lgp-email">
						<?php _e( 'Email Address', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="email" 
						id="lgp-email" 
						name="email" 
						class="lgp-form-control"
						value="<?php echo esc_attr( $user_data['email'] ?? '' ); ?>"
						<?php echo is_user_logged_in() ? 'readonly' : ''; ?>
						required
					>
					<div class="error-message"></div>
				</div>

				<div class="lgp-form-group">
					<label for="lgp-phone">
						<?php _e( 'Phone Number', 'loungenie-portal' ); ?>
						<span class="optional"><?php _e( '(Optional)', 'loungenie-portal' ); ?></span>
					</label>
					<input 
						type="tel" 
						id="lgp-phone" 
						name="phone" 
						class="lgp-form-control"
						value="<?php echo esc_attr( $user_data['phone'] ?? '' ); ?>"
						placeholder="(555) 123-4567"
					>
					<div class="error-message"></div>
					<small class="help-text"><?php _e( 'Format: (555) 123-4567', 'loungenie-portal' ); ?></small>
				</div>
			</div>
		</fieldset>

		<!-- Company and Units -->
		<fieldset>
			<legend class="lgp-form-legend"><?php _e( 'Property Information', 'loungenie-portal' ); ?></legend>

			<?php if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) : ?>
				<div class="lgp-form-group">
					<label for="lgp-company">
						<?php _e( 'Property/Company', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<select 
						id="lgp-company" 
						name="company_id" 
						class="lgp-form-control"
						required
					>
						<option value=""><?php _e( '-- Select Property --', 'loungenie-portal' ); ?></option>
						<?php
						// Fetch companies
						$companies = LGP_Database::get_companies( array( 'status' => 'active' ) );
						foreach ( $companies as $company ) {
							?>
							<option value="<?php echo esc_attr( $company->id ); ?>" <?php selected( $user_data['company_id'] ?? '', $company->id ); ?>>
								<?php echo esc_html( $company->name ); ?>
							</option>
							<?php
						}
						?>
					</select>
					<div class="error-message"></div>
				</div>
			<?php else : ?>
				<input type="hidden" name="company_id" value="<?php echo esc_attr( $user_data['company_id'] ); ?>">
			<?php endif; ?>

			<div class="lgp-form-group">
				<label for="lgp-units-affected">
					<?php _e( 'Number of Units Affected', 'loungenie-portal' ); ?>
					<span class="required">*</span>
				</label>
				<input 
					type="number" 
					id="lgp-units-affected" 
					name="units_affected" 
					class="lgp-form-control"
					min="1" 
					placeholder="1"
					required
				>
				<div class="error-message"></div>
				<small class="help-text"><?php _e( 'How many units are affected by this issue?', 'loungenie-portal' ); ?></small>
			</div>

			<div class="lgp-form-group">
				<label for="lgp-unit-ids">
					<?php _e( 'Select Specific Units (Optional)', 'loungenie-portal' ); ?>
				</label>
				<select 
					id="lgp-unit-ids" 
					name="unit_ids[]" 
					class="lgp-form-control"
					multiple
				>
					<option value=""><?php _e( '-- Select Units --', 'loungenie-portal' ); ?></option>
				</select>
				<small class="help-text"><?php _e( 'Hold Ctrl/Cmd to select multiple units', 'loungenie-portal' ); ?></small>
			</div>
		</fieldset>

		<!-- Ticket Details -->
		<fieldset>
			<legend class="lgp-form-legend"><?php _e( 'Issue Details', 'loungenie-portal' ); ?></legend>

			<div class="lgp-form-row">
				<div class="lgp-form-group">
					<label for="lgp-category">
						<?php _e( 'Category', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<select 
						id="lgp-category" 
						name="category" 
						class="lgp-form-control"
						required
					>
						<option value=""><?php _e( '-- Select Category --', 'loungenie-portal' ); ?></option>
						<option value="maintenance"><?php _e( 'Maintenance Issue', 'loungenie-portal' ); ?></option>
						<option value="billing"><?php _e( 'Billing Question', 'loungenie-portal' ); ?></option>
						<option value="account"><?php _e( 'Account/Access', 'loungenie-portal' ); ?></option>
						<option value="feature_request"><?php _e( 'Feature Request', 'loungenie-portal' ); ?></option>
						<option value="general"><?php _e( 'General Inquiry', 'loungenie-portal' ); ?></option>
						<option value="other"><?php _e( 'Other', 'loungenie-portal' ); ?></option>
					</select>
					<div class="error-message"></div>
				</div>

				<div class="lgp-form-group">
					<label for="lgp-urgency">
						<?php _e( 'Urgency Level', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
					<select 
						id="lgp-urgency" 
						name="urgency" 
						class="lgp-form-control"
						required
					>
						<option value="low"><?php _e( 'Low', 'loungenie-portal' ); ?></option>
						<option value="medium" selected><?php _e( 'Medium', 'loungenie-portal' ); ?></option>
						<option value="high"><?php _e( 'High', 'loungenie-portal' ); ?></option>
						<option value="critical"><?php _e( 'Critical', 'loungenie-portal' ); ?></option>
					</select>
					<div class="error-message"></div>
				</div>
			</div>

			<div class="lgp-form-group">
				<label for="lgp-subject">
					<?php _e( 'Subject', 'loungenie-portal' ); ?>
					<span class="required">*</span>
				</label>
				<input 
					type="text" 
					id="lgp-subject" 
					name="subject" 
					class="lgp-form-control"
					maxlength="100"
					placeholder="<?php _e( 'Brief description of your issue', 'loungenie-portal' ); ?>"
					required
				>
				<div class="error-message"></div>
				<small class="help-text">
					<?php _e( 'Characters: ', 'loungenie-portal' ); ?><span class="char-count">0</span>/100
				</small>
			</div>

			<div class="lgp-form-group">
				<label for="lgp-description">
					<?php _e( 'Detailed Description', 'loungenie-portal' ); ?>
					<span class="required">*</span>
				</label>
				<textarea 
					id="lgp-description" 
					name="description" 
					class="lgp-form-control"
					rows="6"
					placeholder="<?php _e( 'Please provide detailed information about your issue...', 'loungenie-portal' ); ?>"
					required
				></textarea>
				<div class="error-message"></div>
				<small class="help-text">
					<?php _e( 'Minimum 10 characters required', 'loungenie-portal' ); ?>
				</small>
			</div>
		</fieldset>

		<!-- File Attachments -->
		<fieldset>
			<legend class="lgp-form-legend"><?php _e( 'Attachments', 'loungenie-portal' ); ?></legend>

			<div class="lgp-form-group">
				<label><?php _e( 'Upload Files (Optional)', 'loungenie-portal' ); ?></label>
				<div class="file-upload-area" id="lgp-file-upload">
					<div class="file-upload-icon">📎</div>
					<div class="file-upload-text">
						<?php _e( 'Drag & drop files here or click to browse', 'loungenie-portal' ); ?>
					</div>
					<input 
						type="file" 
						id="lgp-attachments" 
						name="attachments[]" 
						multiple
						accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
					>
					<div class="file-upload-hint">
						<?php _e( 'Max 5 files, 10MB each. Allowed: Images, PDF, Office docs, ZIP', 'loungenie-portal' ); ?>
					</div>
				</div>
				<div class="attachments-list"></div>
			</div>
		</fieldset>

		<!-- Consent & Checkboxes -->
		<fieldset>
			<legend class="lgp-form-legend"><?php _e( 'Consent & Preferences', 'loungenie-portal' ); ?></legend>

			<div class="lgp-form-group lgp-checkbox-group">
				<div class="lgp-checkbox-item">
					<input 
						type="checkbox" 
						id="lgp-consent-contact" 
						name="consent_contact" 
						value="1"
						required
					>
					<label for="lgp-consent-contact">
						<?php _e( 'I consent to be contacted about this ticket', 'loungenie-portal' ); ?>
						<span class="required">*</span>
					</label>
				</div>
			</div>

			<div class="lgp-form-group lgp-checkbox-group">
				<div class="lgp-checkbox-item">
					<input 
						type="checkbox" 
						id="lgp-consent-privacy" 
						name="consent_privacy" 
						value="1"
						required
					>
					<label for="lgp-consent-privacy">
						<?php
						printf(
							__( 'I agree to the <a href="%s" target="_blank">Privacy Policy</a>', 'loungenie-portal' ),
							esc_url( home_url( '/privacy-policy/' ) )
						);
						?>
						<span class="required">*</span>
					</label>
				</div>
			</div>
		</fieldset>

		<!-- Form Actions -->
		<div class="lgp-form-actions">
			<button type="submit" class="lgp-btn lgp-btn-primary">
				<?php _e( 'Submit Ticket', 'loungenie-portal' ); ?>
			</button>
			<button type="reset" class="lgp-btn lgp-btn-secondary">
				<?php _e( 'Clear Form', 'loungenie-portal' ); ?>
			</button>
		</div>
	</form>
</div>

<?php
// Localize script with necessary data
wp_localize_script(
	'lgp-support-ticket-form',
	'lgpVars',
	array(
		'ajaxurl'     => admin_url( 'admin-ajax.php' ),
		'ticketNonce' => wp_create_nonce( 'lgp_submit_support_ticket' ),
		'nonce'       => wp_create_nonce( 'lgp_ajax_nonce' ),
		'maxFileSize' => 10 * 1024 * 1024,
		'maxFiles'    => 5,
	)
);

// Prefill user data
if ( is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	wp_localize_script(
		'lgp-support-ticket-form',
		'lgpUserData',
		array(
			'first_name' => $current_user->first_name,
			'last_name'  => $current_user->last_name,
			'email'      => $current_user->user_email,
			'phone'      => get_user_meta( $current_user->ID, 'phone', true ),
			'company_id' => LGP_Auth::get_user_company_id(),
		)
	);
}
?>
