# LounGenie Portal - Custom Login System

## Overview

The LounGenie Portal includes a comprehensive custom login page system with:

- **Role-Based Authentication** - Separate login flows for Support and Partner users
- **Microsoft SSO** - Support users authenticate via Microsoft Outlook / Microsoft 365
- **WordPress Authentication** - Partner users use standard WordPress username/password
- **Security Best Practices** - Nonce validation, no user enumeration, secure error handling
- **Beautiful UI** - Matches LounGenie Portal design system with 60-30-10 color rule
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile devices
- **Accessibility** - WCAG 2.1 AA compliant with keyboard navigation and screen reader support
- **Performance** - Optimized for shared hosting environments

---

## Architecture

### Components

#### 1. **Custom Login Template** (`templates/custom-login.php`)
- Multi-step login flow: Role Selection → Authentication Method → Secure Login
- Role-specific UI for Support (SSO) and Partner (Password)
- Error messaging without user enumeration
- Responsive HTML with semantic markup
- Mobile-optimized form inputs

#### 2. **Login Handler Class** (`includes/class-lgp-login-handler.php`)
- Processes both Partner and SSO login attempts
- Validates nonces on all form submissions
- Verifies user roles and capabilities
- Manages account status checks
- Handles role-based redirects
- Integrates with WordPress authentication

#### 3. **Microsoft SSO Handler** (`includes/class-lgp-microsoft-sso-handler.php`)
- OAuth 2.0 authentication flow
- Microsoft Graph API integration
- CSRF protection with state parameter
- Automatic user creation for valid SSO users
- Token management and caching

#### 4. **Login Styles** (`assets/css/login-page.css`)
- 60-30-10 color system (verified)
- Role-specific theming
- Accessibility features (skip links, focus states)
- Mobile responsiveness with media queries
- Dark mode and high contrast support

---

## User Flows

### Support Team Login Flow

```
1. User lands on login page
   ↓
2. Selects "Support" role
   ↓
3. Clicks "Sign in with Microsoft" button
   ↓
4. Redirected to Microsoft login (Outlook/Microsoft 365)
   ↓
5. User authenticates with Microsoft credentials
   ↓
6. Microsoft redirects back to WordPress with authorization code
   ↓
7. OAuth token exchanged securely
   ↓
8. User data retrieved from Microsoft Graph API
   ↓
9. User account created/updated if needed
   ↓
10. Support role verified
   ↓
11. WordPress session established
   ↓
12. Redirected to Support Dashboard
```

### Partner Login Flow

```
1. User lands on login page
   ↓
2. Selects "Partner" role
   ↓
3. Enters username/email and password
   ↓
4. Nonce validated
   ↓
5. WordPress authenticates credentials
   ↓
6. Partner role verified
   ↓
7. Account status checked
   ↓
8. WordPress session established
   ↓
9. Last login time recorded
   ↓
10. Redirected to Partner Dashboard
```

---

## Configuration

### Setup for Support (Microsoft SSO)

#### Option 1: Using WordPress Constants (Recommended for Production)

Add to `wp-config.php`:

```php
// Microsoft SSO Configuration
define('LGP_MICROSOFT_CLIENT_ID', 'your-client-id-here');
define('LGP_MICROSOFT_CLIENT_SECRET', 'your-client-secret-here');
define('LGP_MICROSOFT_TENANT_ID', 'your-tenant-id-or-common');
```

#### Option 2: Using WordPress Admin

1. Go to WordPress Admin Dashboard
2. Navigate to Settings → LounGenie Portal → SSO Configuration
3. Enter:
   - Client ID (from Azure portal)
   - Client Secret (from Azure portal)
   - Tenant ID (from Azure portal or leave as "common")
4. Click Save

### Azure Active Directory Setup

**Step 1: Register Application**

1. Visit https://portal.azure.com
2. Navigate to Azure Active Directory → App registrations
3. Click "New registration"
4. Enter Name: "LounGenie Portal"
5. Select account type: "Accounts in this organizational directory only"
6. Click Register

**Step 2: Configure Redirect URI**

1. Go to Authentication
2. Add Redirect URI:
   ```
   https://yoursite.com/wp-login.php?action=lgp_sso_callback
   ```
3. Check "Access tokens" and "ID tokens"
4. Click Save

**Step 3: Create Client Secret**

1. Go to Certificates & secrets
2. Click "New client secret"
3. Add description: "LounGenie Portal"
4. Select expiration (24 months recommended)
5. Click Add
6. **Copy the secret value immediately** (cannot be retrieved later)

**Step 4: Configure API Permissions**

1. Go to API permissions
2. Click "Add a permission"
3. Select "Microsoft Graph"
4. Add Delegated permissions: openid, profile, email
5. Grant admin consent

**Step 5: Get Tenant ID**

1. From Azure portal home, go to Azure Active Directory
2. Copy the "Tenant ID" value
3. Add to wp-config.php as `LGP_MICROSOFT_TENANT_ID`

---

## Security Implementation

### Nonce Validation

All login forms include nonce fields:

```php
<?php wp_nonce_field('lgp_partner_login', 'lgp_login_nonce'); ?>
<?php wp_nonce_field('lgp_sso_login', 'lgp_sso_nonce'); ?>
```

Nonces are verified before processing:

```php
if (!wp_verify_nonce($_POST['lgp_login_nonce'], 'lgp_partner_login')) {
    // Reject request
}
```

### No User Enumeration

Error messages don't reveal whether email/username exists:

❌ Bad: "This email is not registered"
✅ Good: "Invalid username or password"

All authentication failures return generic error message while logging actual error for debugging.

### Input Sanitization

```php
$user_login = sanitize_text_field($_POST['user_login']);
$user_password = $_POST['user_password']; // Not sanitized (raw for hash comparison)
$redirect_to = esc_url_raw($_POST['redirect_to']); // Safe URL validation
```

### Output Escaping

```php
<?php echo esc_html($error_message); ?>
<?php echo esc_attr($redirect_url); ?>
<?php echo esc_url($link_href); ?>
```

### OAuth Security

- **State Parameter**: CSRF protection using random state in OAuth flow
- **HTTPS Only**: All OAuth communication requires HTTPS
- **Token Validation**: Access tokens verified with Microsoft servers
- **Scope Limitation**: Only requesting `openid`, `profile`, `email` scopes
- **Token Storage**: Tokens stored in transients (not persistent)

### Capability Checks

```php
// Verify user has required role
if (!user_has_cap($user_id, 'lgp_partner')) {
    wp_die('Unauthorized');
}
```

### Account Status

Users can be disabled via custom meta flag:

```php
$is_active = get_user_meta($user_id, 'lgp_account_active', true);
if ($is_active === '0') {
    // Reject login
}
```

---

## Features

### Role-Based Redirects

After login, users are redirected to appropriate dashboard:

```php
// Support users → Support Dashboard
home_url('/support-dashboard')

// Partner users → Partner Dashboard
home_url('/partner-dashboard')
```

Customizable via filter:

```php
add_filter('lgp_partner_redirect', function($redirect, $user_id) {
    return home_url('/custom-partner-page');
}, 10, 2);
```

### Session Management

- **Remember Me**: 14-day persistent session for Partners
- **Last Login**: Stored in user meta for audit logging
- **SSO Transients**: Temporary storage during OAuth flow

### Account Deactivation

Admin can disable accounts:

```php
update_user_meta($user_id, 'lgp_account_active', '0');
```

Disabled users cannot log in but account data is preserved.

### Audit Logging

Login events trigger custom actions:

```php
// Successful login
do_action('lgp_login_success', $user_id, 'partner');
do_action('lgp_login_success', $user_id, 'support');

// Failed login
do_action('lgp_login_failed', $error_code, $username);

// Unauthorized attempt
do_action('lgp_unauthorized_login', $user_id, 'required_role');

// SSO events
do_action('lgp_sso_user_created', $user_id, $user_data);
do_action('lgp_sso_error', $error, $description);
```

---

## Customization

### Modify Login Page Template

Edit `/templates/custom-login.php`:

```php
// Change branding text
<h2><?php esc_html_e('Welcome to LounGenie Portal', 'loungenie-portal'); ?></h2>

// Add custom features list
<div class="lgp-feature">
    <span class="lgp-feature-icon">🎯</span>
    <span class="lgp-feature-text">Your custom feature</span>
</div>
```

### Override CSS

Add to theme's `functions.php`:

```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('custom-login-css', get_stylesheet_directory_uri() . '/custom-login.css');
}, 20);
```

### Custom Redirect Logic

```php
add_filter('lgp_login_redirect', function($redirect, $fallback, $user_id) {
    $user = get_user_by('id', $user_id);
    
    // Custom redirect based on company
    $company_id = get_user_meta($user_id, 'lgp_company_id', true);
    if ($company_id) {
        return home_url('/company/' . $company_id . '/dashboard');
    }
    
    return $redirect;
}, 10, 3);
```

### Custom Error Handling

```php
add_action('lgp_login_failed', function($error_code, $username) {
    // Log to custom analytics
    error_log("Login failed for $username: $error_code");
}, 10, 2);
```

---

## Troubleshooting

### SSO Not Working

**Problem**: Microsoft login button does nothing
**Solution**:
1. Check Azure app registration exists
2. Verify Client ID and Client Secret are correct
3. Ensure Redirect URI matches exactly (including protocol and trailing slash)
4. Check that API permissions are granted admin consent
5. Review browser console for JavaScript errors

### "Invalid State Parameter" Error

**Problem**: Error after clicking Microsoft login button
**Solution**:
1. This is a CSRF protection error
2. Check server time is synchronized (NTP)
3. Clear browser cookies
4. Try in incognito/private mode
5. Check PHP session configuration

### Users Can't Create Accounts via SSO

**Problem**: New SSO users can't log in
**Solution**:
1. Enable user creation: `add_filter('lgp_allow_sso_user_creation', '__return_true');`
2. Verify Azure app has permission to user.read
3. Check Microsoft email is valid
4. Ensure support role exists in WordPress

### Login Redirect Not Working

**Problem**: After login, redirected to /wp-admin instead of dashboard
**Solution**:
1. Check dashboard page exists at `/partner-dashboard` or `/support-dashboard`
2. Verify redirect URLs in dashboard page settings
3. Use filter to debug: `add_filter('lgp_login_redirect', 'var_dump')`
4. Clear WordPress cache if using caching plugin

### "Account Disabled" Error

**Problem**: User can't login but account exists
**Solution**:
1. Check user meta: `get_user_meta($user_id, 'lgp_account_active')`
2. Re-enable account: `update_user_meta($user_id, 'lgp_account_active', '1')`
3. Verify user has correct role: `lgp_partner` or `lgp_support`

---

## Performance Optimization

### Shared Hosting Considerations

The login system is optimized for shared hosting:

1. **Minimal Database Queries**: Efficient user lookups
2. **Transient-Based Storage**: Temporary SSO data stored in database transients (auto-cleaned)
3. **No File Operations**: All processing in memory
4. **Lazy Loading**: CSS and JS only loaded on login page
5. **Short Timeouts**: OAuth state expires after 1 hour

### Database Queries

- Partner login: ~5 queries (authentication, role check, update metadata)
- SSO login: ~3 queries (token exchange external, user lookup, create/update user, role assignment)

### Cache Strategy

- User lookups use WordPress object cache when available
- SSO state stored in transients with 1-hour expiration
- Login page cached for 5 minutes (if caching enabled)

---

## API Reference

### Login Handler Methods

```php
// Get custom login URL
$url = \LounGenie\Portal\Login_Handler::get_custom_login_url('partner', $redirect);

// Get role-based redirect
$redirect = \LounGenie\Portal\Login_Handler::get_role_redirect($user_id);

// Initialize login system
\LounGenie\Portal\Login_Handler::init();
```

### Microsoft SSO Methods

```php
// Get singleton instance
$sso = \LounGenie\Portal\Microsoft_SSO::get_instance();

// Check if configured
if ($sso->is_configured()) {
    $sso->authenticate();
}

// Save configuration
\LounGenie\Portal\Microsoft_SSO::save_config($client_id, $client_secret, $tenant_id);

// Get configuration
$config = \LounGenie\Portal\Microsoft_SSO::get_config();

// Get setup docs
$docs = \LounGenie\Portal\Microsoft_SSO::get_setup_documentation();
```

---

## Hooks & Filters

### Actions

```php
// After successful login
do_action('lgp_login_success', $user_id, $role);

// After failed login
do_action('lgp_login_failed', $error_code, $username);

// After unauthorized login attempt
do_action('lgp_unauthorized_login', $user_id, $required_role);

// After logout
do_action('lgp_logout', $user_id);

// When SSO user is created
do_action('lgp_sso_user_created', $user_id, $user_data);

// When SSO encounters error
do_action('lgp_sso_error', $error, $description);

// When SSO config is updated
do_action('lgp_sso_config_updated', $config);
```

### Filters

```php
// Customize login redirect
apply_filters('lgp_login_redirect', $redirect, $fallback, $user_id);

// Customize partner redirect
apply_filters('lgp_partner_redirect', $redirect, $user_id);

// Customize support redirect
apply_filters('lgp_support_redirect', $redirect, $user_id);

// Allow SSO user creation
apply_filters('lgp_allow_sso_user_creation', false);

// Customize error messages
apply_filters('lgp_login_error_message', $message, $error_code);
```

---

## Best Practices

### For Administrators

1. **Secure Configuration**: Use wp-config.php constants for production, not database options
2. **Regular Audits**: Check login audit logs regularly
3. **User Management**: Disable accounts instead of deleting for audit trail
4. **Backup**: Always backup database before deploying updates
5. **HTTPS**: Ensure site uses HTTPS (required for SSO)

### For Developers

1. **Extend Safely**: Use hooks and filters instead of modifying core files
2. **Test Thoroughly**: Test all login flows before deploying
3. **Error Handling**: Log errors for debugging but don't expose to users
4. **Performance**: Profile login flow on shared hosting
5. **Security**: Keep Microsoft client secret secure in constants, never in code

### For Users

1. **Password Security**: Partners should use strong, unique passwords
2. **Microsoft Account**: Support users should use updated Microsoft account credentials
3. **Session Management**: Log out when done, especially on shared computers
4. **Clear Cookies**: Clear browser cookies if login problems occur

---

## Compliance

### GDPR

- No unnecessary personal data collection
- Only email, name, and role stored
- User data can be exported via WordPress data tools
- Users can request account deletion

### CCPA

- Collection limited to minimum required
- User can opt out of SSO (use WordPress password login)
- Data retention policies honored

### WCAG 2.1 Level AA

- Keyboard navigation fully supported
- Screen reader compatible
- High contrast mode supported
- Accessible error messages
- Focus indicators on all interactive elements

---

## Support & Maintenance

### File Locations

```
loungenie-portal/
├── templates/
│   └── custom-login.php          # Login page HTML/PHP
├── includes/
│   ├── class-lgp-login-handler.php       # Login processing
│   └── class-lgp-microsoft-sso-handler.php  # SSO integration
├── assets/css/
│   └── login-page.css            # Login page styles
└── CUSTOM_LOGIN_SETUP.md         # This file
```

### Version History

- **1.8.0**: Initial release with Partner and Support login flows

### Future Enhancements

- [ ] Social login (Google, GitHub)
- [ ] Two-factor authentication
- [ ] Passwordless login (magic links)
- [ ] Single sign-on for other providers
- [ ] Advanced audit logging dashboard
- [ ] Rate limiting per IP
- [ ] Geographic login restrictions

---

## Questions?

For issues or questions about the custom login system:

1. Check this documentation
2. Review error logs: `/wp-content/debug.log`
3. Test login flows in incognito mode
4. Verify configuration matches Azure setup
5. Contact plugin support team

---

**Created**: December 18, 2025
**Version**: 1.8.0
**Status**: Production Ready
