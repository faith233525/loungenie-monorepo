<?php
/**
 * Support Ticket Submission Form Component
 * Production-ready form with validation, file uploads, and auto-prefill for logged-in users
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH') ) {
    exit;
}

// Get current user info
$current_user    = wp_get_current_user();
$user_company_id = LGP_Auth::get_user_company_id();

// Pre-fill user data if logged in
$prefilled_first_name = '';
$prefilled_last_name  = '';
$prefilled_email      = '';
$prefilled_phone      = '';
$prefilled_company_id = $user_company_id ?? '';

if (is_user_logged_in() ) {
    $prefilled_first_name = get_user_meta($current_user->ID, 'first_name', true) ?? '';
    $prefilled_last_name  = get_user_meta($current_user->ID, 'last_name', true) ?? '';
    $prefilled_email      = $current_user->user_email ?? '';
    $prefilled_phone      = get_user_meta($current_user->ID, 'phone', true) ?? '';
}

// Get available companies if not logged in or if current user is admin
$companies = array();
if (! is_user_logged_in() || current_user_can('manage_options') ) {
    global $wpdb;
    $companies = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}lgp_companies ORDER BY name ASC");
}

// Get available units for the company
$units = array();
if ($prefilled_company_id ) {
    global $wpdb;
    $units = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}lgp_units WHERE company_id = %d ORDER BY name ASC",
            $prefilled_company_id
        )
    );
}

// Auto-generated ticket ID (for reference in email)
$ticket_ref = 'TKT-' . date('YmdHi') . '-' . wp_rand(1000, 9999);

// Nonce for form security
$nonce = wp_create_nonce('lgp_submit_support_ticket');

?>

<div class="lgp-support-ticket-form-wrapper">
    <form 
        id="lgp-support-ticket-form" 
        class="lgp-support-ticket-form" 
        method="POST" 
        enctype="multipart/form-data"
        novalidate
    >
        
        <!-- Form Header -->
        <div class="lgp-form-header">
            <h3><?php esc_html_e('Submit a Support Ticket', 'loungenie-portal'); ?></h3>
            <p class="lgp-form-description">
                <?php esc_html_e('Please provide detailed information about your issue. Required fields are marked with an asterisk (*).', 'loungenie-portal'); ?>
            </p>
            <?php if (! empty($ticket_ref) ) : ?>
                <div class="lgp-form-reference">
                    <small><?php echo esc_html__('Reference: ', 'loungenie-portal') . esc_html($ticket_ref); ?></small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Personal Information Section -->
        <fieldset class="lgp-form-fieldset">
            <legend class="lgp-form-legend"><?php esc_html_e('Personal Information', 'loungenie-portal'); ?></legend>

            <div class="lgp-form-row lgp-form-row-two-cols">
                <!-- First Name -->
                <div class="lgp-form-group">
                    <label for="lgp-first-name" class="lgp-label">
                        <?php esc_html_e('First Name', 'loungenie-portal'); ?>
                        <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                    </label>
                    <input
                        type="text"
                        id="lgp-first-name"
                        name="first_name"
                        class="lgp-form-input"
                        value="<?php echo esc_attr($prefilled_first_name); ?>"
                        placeholder="<?php esc_attr_e('John', 'loungenie-portal'); ?>"
                        required
                        maxlength="50"
                        pattern="[a-zA-Z\s'-]{2,}"
                        aria-required="true"
                        aria-describedby="lgp-first-name-error"
                    />
                    <div id="lgp-first-name-error" class="lgp-form-error" role="alert"></div>
                </div>

                <!-- Last Name -->
                <div class="lgp-form-group">
                    <label for="lgp-last-name" class="lgp-label">
                        <?php esc_html_e('Last Name', 'loungenie-portal'); ?>
                        <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                    </label>
                    <input
                        type="text"
                        id="lgp-last-name"
                        name="last_name"
                        class="lgp-form-input"
                        value="<?php echo esc_attr($prefilled_last_name); ?>"
                        placeholder="<?php esc_attr_e('Doe', 'loungenie-portal'); ?>"
                        required
                        maxlength="50"
                        pattern="[a-zA-Z\s'-]{2,}"
                        aria-required="true"
                        aria-describedby="lgp-last-name-error"
                    />
                    <div id="lgp-last-name-error" class="lgp-form-error" role="alert"></div>
                </div>
            </div>

            <div class="lgp-form-row lgp-form-row-two-cols">
                <!-- Email -->
                <div class="lgp-form-group">
                    <label for="lgp-email" class="lgp-label">
                        <?php esc_html_e('Email Address', 'loungenie-portal'); ?>
                        <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                    </label>
                    <input
                        type="email"
                        id="lgp-email"
                        name="email"
                        class="lgp-form-input"
                        value="<?php echo esc_attr($prefilled_email); ?>"
                        placeholder="<?php esc_attr_e('john.doe@example.com', 'loungenie-portal'); ?>"
                        required
                        aria-required="true"
                        aria-describedby="lgp-email-error"
                        <?php echo is_user_logged_in() ? 'readonly' : ''; ?>
                    />
                    <div id="lgp-email-error" class="lgp-form-error" role="alert"></div>
                </div>

                <!-- Phone Number -->
                <div class="lgp-form-group">
                    <label for="lgp-phone" class="lgp-label">
                        <?php esc_html_e('Phone Number', 'loungenie-portal'); ?>
                        <span class="lgp-optional"><?php esc_html_e('(optional)', 'loungenie-portal'); ?></span>
                    </label>
                    <input
                        type="tel"
                        id="lgp-phone"
                        name="phone"
                        class="lgp-form-input"
                        value="<?php echo esc_attr($prefilled_phone); ?>"
                        placeholder="<?php esc_attr_e('(123) 456-7890', 'loungenie-portal'); ?>"
                        maxlength="20"
                        pattern="^[\d\s()+-]*$"
                        aria-describedby="lgp-phone-error"
                    />
                    <div id="lgp-phone-error" class="lgp-form-error" role="alert"></div>
                    <small class="lgp-form-hint"><?php esc_html_e('Format: (123) 456-7890 or +1-123-456-7890', 'loungenie-portal'); ?></small>
                </div>
            </div>
        </fieldset>

        <!-- Company & Unit Information Section -->
        <fieldset class="lgp-form-fieldset">
            <legend class="lgp-form-legend"><?php esc_html_e('Company & Unit Information', 'loungenie-portal'); ?></legend>

            <?php if (! is_user_logged_in() ) : ?>
                <!-- Company Selection (for non-logged-in users) -->
                <div class="lgp-form-group">
                    <label for="lgp-company" class="lgp-label">
                <?php esc_html_e('Company', 'loungenie-portal'); ?>
                        <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                    </label>
                    <select
                        id="lgp-company"
                        name="company_id"
                        class="lgp-form-select"
                        required
                        aria-required="true"
                        aria-describedby="lgp-company-error"
                    >
                        <option value=""><?php esc_html_e('-- Select your company --', 'loungenie-portal'); ?></option>
                <?php foreach ( $companies as $company ) : ?>
                            <option value="<?php echo esc_attr($company->id); ?>">
                    <?php echo esc_html($company->name); ?>
                            </option>
                <?php endforeach; ?>
                    </select>
                    <div id="lgp-company-error" class="lgp-form-error" role="alert"></div>
                </div>
            <?php else : ?>
                <!-- Company Display (for logged-in users) -->
                <div class="lgp-form-group">
                    <label class="lgp-label"><?php esc_html_e('Company', 'loungenie-portal'); ?></label>
                    <div class="lgp-form-static">
                <?php
                global $wpdb;
                $company = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
                        $prefilled_company_id
                    )
                );
                        echo $company ? esc_html($company->name) : esc_html__('N/A', 'loungenie-portal');
                ?>
                    </div>
                    <input type="hidden" name="company_id" value="<?php echo esc_attr($prefilled_company_id); ?>" />
                </div>
            <?php endif; ?>

            <!-- Number of Units Affected -->
            <div class="lgp-form-group">
                <label for="lgp-units-affected" class="lgp-label">
                    <?php esc_html_e('Number of Units Affected', 'loungenie-portal'); ?>
                    <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                </label>
                <div class="lgp-form-radio-group" role="radiogroup" aria-required="true">
                    <label class="lgp-radio-label">
                        <input type="radio" name="units_affected" value="1" class="lgp-radio-input" required />
                        <span><?php esc_html_e('1 Unit', 'loungenie-portal'); ?></span>
                    </label>
                    <label class="lgp-radio-label">
                        <input type="radio" name="units_affected" value="2-5" class="lgp-radio-input" />
                        <span><?php esc_html_e('2-5 Units', 'loungenie-portal'); ?></span>
                    </label>
                    <label class="lgp-radio-label">
                        <input type="radio" name="units_affected" value="6-10" class="lgp-radio-input" />
                        <span><?php esc_html_e('6-10 Units', 'loungenie-portal'); ?></span>
                    </label>
                    <label class="lgp-radio-label">
                        <input type="radio" name="units_affected" value="10+" class="lgp-radio-input" />
                        <span><?php esc_html_e('10+ Units', 'loungenie-portal'); ?></span>
                    </label>
                </div>
                <div id="lgp-units-affected-error" class="lgp-form-error" role="alert"></div>
            </div>

            <!-- Phase 2B: unit_ids[] selector removed -->
            <!-- Units tracked via aggregation only (company-level color counts) -->
            <!-- Partners specify range via "Number of Units Affected" above -->
        </fieldset>

        <!-- Issue Details Section -->
        <fieldset class="lgp-form-fieldset">
            <legend class="lgp-form-legend"><?php esc_html_e('Issue Details', 'loungenie-portal'); ?></legend>

            <!-- Category/Request Type -->
            <div class="lgp-form-group">
                <label for="lgp-category" class="lgp-label">
                    <?php esc_html_e('Request Category', 'loungenie-portal'); ?>
                    <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                </label>
                <select
                    id="lgp-category"
                    name="category"
                    class="lgp-form-select"
                    required
                    aria-required="true"
                    aria-describedby="lgp-category-error"
                >
                    <option value=""><?php esc_html_e('-- Select category --', 'loungenie-portal'); ?></option>
                    <option value="maintenance"><?php esc_html_e('Maintenance Request', 'loungenie-portal'); ?></option>
                    <option value="software_issue"><?php esc_html_e('Software Issue', 'loungenie-portal'); ?></option>
                    <option value="hardware_issue"><?php esc_html_e('Hardware Issue', 'loungenie-portal'); ?></option>
                    <option value="general_inquiry"><?php esc_html_e('General Inquiry', 'loungenie-portal'); ?></option>
                    <option value="feature_request"><?php esc_html_e('Feature Request', 'loungenie-portal'); ?></option>
                    <option value="billing"><?php esc_html_e('Billing/Account', 'loungenie-portal'); ?></option>
                    <option value="other"><?php esc_html_e('Other', 'loungenie-portal'); ?></option>
                </select>
                <div id="lgp-category-error" class="lgp-form-error" role="alert"></div>
            </div>

            <!-- Urgency Level -->
            <div class="lgp-form-group">
                <label for="lgp-urgency" class="lgp-label">
                    <?php esc_html_e('Urgency Level', 'loungenie-portal'); ?>
                    <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                </label>
                <div class="lgp-form-radio-group" role="radiogroup" aria-required="true">
                    <label class="lgp-radio-label">
                        <input type="radio" name="urgency" value="normal" class="lgp-radio-input" checked />
                        <span><span class="lgp-urgency-icon lgp-urgency-normal"></span><?php esc_html_e('Normal (Non-urgent)', 'loungenie-portal'); ?></span>
                    </label>
                    <label class="lgp-radio-label">
                        <input type="radio" name="urgency" value="high" class="lgp-radio-input" />
                        <span><span class="lgp-urgency-icon lgp-urgency-high"></span><?php esc_html_e('High (Soon needed)', 'loungenie-portal'); ?></span>
                    </label>
                    <label class="lgp-radio-label">
                        <input type="radio" name="urgency" value="critical" class="lgp-radio-input" />
                        <span><span class="lgp-urgency-icon lgp-urgency-critical"></span><?php esc_html_e('Critical (Down/Emergency)', 'loungenie-portal'); ?></span>
                    </label>
                </div>
                <div id="lgp-urgency-error" class="lgp-form-error" role="alert"></div>
            </div>

            <!-- Subject -->
            <div class="lgp-form-group">
                <label for="lgp-subject" class="lgp-label">
                    <?php esc_html_e('Subject', 'loungenie-portal'); ?>
                    <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                </label>
                <input
                    type="text"
                    id="lgp-subject"
                    name="subject"
                    class="lgp-form-input"
                    placeholder="<?php esc_attr_e('Brief description of your issue (max 100 characters)', 'loungenie-portal'); ?>"
                    required
                    maxlength="100"
                    minlength="5"
                    aria-required="true"
                    aria-describedby="lgp-subject-error lgp-subject-counter"
                />
                <div id="lgp-subject-error" class="lgp-form-error" role="alert"></div>
                <div id="lgp-subject-counter" class="lgp-form-counter">
                    <span id="lgp-subject-length">0</span> / 100
                </div>
            </div>

            <!-- Detailed Description -->
            <div class="lgp-form-group">
                <label for="lgp-description" class="lgp-label">
                    <?php esc_html_e('Detailed Description', 'loungenie-portal'); ?>
                    <span class="lgp-required" aria-label="<?php esc_attr_e('required', 'loungenie-portal'); ?>">*</span>
                </label>
                <textarea
                    id="lgp-description"
                    name="description"
                    class="lgp-form-textarea"
                    placeholder="<?php esc_attr_e('Please provide detailed information about your issue. Include steps to reproduce, error messages, etc.', 'loungenie-portal'); ?>"
                    required
                    minlength="10"
                    maxlength="5000"
                    rows="6"
                    aria-required="true"
                    aria-describedby="lgp-description-error lgp-description-counter"
                ></textarea>
                <div id="lgp-description-error" class="lgp-form-error" role="alert"></div>
                <div id="lgp-description-counter" class="lgp-form-counter">
                    <span id="lgp-description-length">0</span> / 5000
                </div>
            </div>
        </fieldset>

        <!-- Attachments Section -->
        <fieldset class="lgp-form-fieldset">
            <legend class="lgp-form-legend"><?php esc_html_e('Attachments', 'loungenie-portal'); ?></legend>

            <div class="lgp-form-group">
                <label for="lgp-attachments" class="lgp-label">
                    <?php esc_html_e('Upload Files', 'loungenie-portal'); ?>
                    <span class="lgp-optional"><?php esc_html_e('(optional)', 'loungenie-portal'); ?></span>
                </label>

                <!-- File Input -->
                <div class="lgp-file-upload-zone" id="lgp-file-upload-zone">
                    <input
                        type="file"
                        id="lgp-attachments"
                        name="attachments[]"
                        class="lgp-file-input"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                        aria-describedby="lgp-attachments-hint lgp-attachments-error"
                    />
                    <label for="lgp-attachments" class="lgp-file-upload-label">
                        <svg class="lgp-file-upload-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm3 2a1 1 0 100 2h6a1 1 0 100-2H5z"></path>
                        </svg>
                        <span class="lgp-file-upload-text">
                            <?php esc_html_e('Click to upload or drag and drop', 'loungenie-portal'); ?>
                        </span>
                        <span class="lgp-file-upload-subtext">
                            <?php esc_html_e('PNG, JPG, PDF, DOCX up to 10MB each (max 5 files)', 'loungenie-portal'); ?>
                        </span>
                    </label>
                </div>

                <!-- File List -->
                <div id="lgp-file-list" class="lgp-file-list" style="display: none;">
                    <h4><?php esc_html_e('Selected Files:', 'loungenie-portal'); ?></h4>
                    <ul id="lgp-file-list-items"></ul>
                    <p id="lgp-file-count" class="lgp-form-counter"></p>
                </div>

                <div id="lgp-attachments-error" class="lgp-form-error" role="alert"></div>
                <small id="lgp-attachments-hint" class="lgp-form-hint">
                    <?php esc_html_e('Supported formats: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP. Max 10MB per file, 5 files total.', 'loungenie-portal'); ?>
                </small>
            </div>
        </fieldset>

        <!-- Consent Section -->
        <fieldset class="lgp-form-fieldset">
            <legend class="lgp-form-legend"><?php esc_html_e('Acknowledgments', 'loungenie-portal'); ?></legend>

            <div class="lgp-form-group">
                <label class="lgp-checkbox-label">
                    <input
                        type="checkbox"
                        name="consent_contact"
                        value="1"
                        class="lgp-checkbox-input"
                        checked
                        required
                        aria-required="true"
                    />
                    <span>
                        <?php esc_html_e('I agree to be contacted via email or phone regarding this support ticket.', 'loungenie-portal'); ?>
                    </span>
                </label>
                <div id="lgp-consent-contact-error" class="lgp-form-error" role="alert"></div>
            </div>

            <div class="lgp-form-group">
                <label class="lgp-checkbox-label">
                    <input
                        type="checkbox"
                        name="consent_privacy"
                        value="1"
                        class="lgp-checkbox-input"
                        required
                        aria-required="true"
                    />
                    <span>
                        <?php
                        printf(
                            esc_html__('I have read and agree to the %1$sprivacy policy%2$s and understand my data will be used to process this support request.', 'loungenie-portal'),
                            '<a href="' . esc_url(home_url('/privacy-policy')) . '" target="_blank" rel="noopener">',
                            '</a>'
                        );
                        ?>
                    </span>
                </label>
                <div id="lgp-consent-privacy-error" class="lgp-form-error" role="alert"></div>
            </div>
        </fieldset>

        <!-- Form Actions -->
        <div class="lgp-form-actions">
            <button 
                type="submit" 
                class="lgp-btn lgp-btn-primary lgp-btn-large"
                id="lgp-submit-btn"
            >
                <span class="lgp-btn-text"><?php esc_html_e('Submit Support Ticket', 'loungenie-portal'); ?></span>
                <span class="lgp-btn-spinner" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                </span>
            </button>

            <button 
                type="reset" 
                class="lgp-btn lgp-btn-secondary lgp-btn-large"
            >
                <?php esc_html_e('Clear Form', 'loungenie-portal'); ?>
            </button>
        </div>

        <!-- Hidden Fields -->
        <input type="hidden" name="action" value="lgp_submit_support_ticket" />
        <input type="hidden" name="ticket_reference" value="<?php echo esc_attr($ticket_ref); ?>" />
        <?php wp_nonce_field('lgp_submit_support_ticket', 'lgp_ticket_nonce'); ?>

        <!-- Success Message (initially hidden) -->
        <div id="lgp-success-message" class="lgp-alert lgp-alert-success" style="display: none;" role="alert">
            <div class="lgp-alert-content">
                <h4><?php esc_html_e('Success!', 'loungenie-portal'); ?></h4>
                <p><?php esc_html_e('Your support ticket has been submitted successfully.', 'loungenie-portal'); ?></p>
                <p id="lgp-success-ticket-id"></p>
                <p class="lgp-alert-subtext">
                    <?php esc_html_e('You will receive a confirmation email shortly with your ticket details.', 'loungenie-portal'); ?>
                </p>
            </div>
        </div>

    </form>
</div>
