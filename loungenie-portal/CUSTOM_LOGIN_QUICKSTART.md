# Custom Login System - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Access the Custom Login Page

The custom login page is automatically available at:

```
https://yoursite.com/wp-login.php?action=login
```

Or use these convenience links:

```php
// In your template files:
<?php
$login_url = \LounGenie\Portal\Login_Handler::get_custom_login_url('select');
$partner_login = \LounGenie\Portal\Login_Handler::get_custom_login_url('partner');
$support_login = \LounGenie\Portal\Login_Handler::get_custom_login_url('support');
?>
```

### Step 2: Enable Microsoft SSO (Support Users)

Add to `wp-config.php`:

```php
define('LGP_MICROSOFT_CLIENT_ID', 'your-client-id');
define('LGP_MICROSOFT_CLIENT_SECRET', 'your-client-secret');
define('LGP_MICROSOFT_TENANT_ID', 'common'); // or your specific tenant ID
```

**Where to find these values:**
1. Go to https://portal.azure.com
2. Search for "App registrations"
3. Register new app called "LounGenie Portal"
4. Add Redirect URI: `https://yoursite.com/wp-login.php?action=lgp_sso_callback`
5. Create Client Secret
6. Copy Client ID, Client Secret, and Tenant ID

### Step 3: Configure Azure (5-10 minutes)

**Azure Portal Steps:**

1. **Create App Registration**
   - Azure AD → App registrations → New registration
   - Name: "LounGenie Portal"
   - Account type: Organizational directory only
   - Click Register

2. **Configure Redirect URI**
   - Go to Authentication
   - Add URI: `https://yoursite.com/wp-login.php?action=lgp_sso_callback`
   - Check "Access tokens" and "ID tokens"
   - Save

3. **Create Client Secret**
   - Go to Certificates & secrets
   - Click "New client secret"
   - Expiration: 24 months
   - Click Add
   - **COPY THE SECRET VALUE** (shown only once)

4. **Add API Permissions**
   - Go to API permissions
   - Add permission → Microsoft Graph
   - Select "Delegated permissions"
   - Add: openid, profile, email
   - Grant admin consent

5. **Get Values**
   - Overview page shows "Application (client) ID" ← Your Client ID
   - Overview page shows "Directory (tenant) ID" ← Your Tenant ID
   - Certificates & secrets shows the secret ← Your Client Secret

### Step 4: Test the Login Page

**Partner Login:**
1. Visit: `https://yoursite.com/wp-login.php?action=login`
2. Click "Partner"
3. Enter username and password
4. Should redirect to Partner Dashboard

**Support Login:**
1. Visit: `https://yoursite.com/wp-login.php?action=login`
2. Click "Support"
3. Click "Sign in with Microsoft"
4. Complete Microsoft authentication
5. Should redirect to Support Dashboard

### Step 5: Add Login Links to Your Site

**In Theme Menu:**
```php
add_filter('nav_menu_link_attributes', function($atts, $item, $args) {
    if ($item->title === 'Portal' || $item->url === 'portal') {
        $atts['href'] = \LounGenie\Portal\Login_Handler::get_custom_login_url('select');
    }
    return $atts;
}, 10, 3);
```

**In Theme Template:**
```html
<a href="<?php echo \LounGenie\Portal\Login_Handler::get_custom_login_url('select'); ?>" 
   class="btn btn-primary">
    Enter Portal
</a>
```

**Direct Links:**
```html
<!-- Partner Login -->
<a href="/wp-login.php?action=login&login_type=partner">Partner Sign In</a>

<!-- Support Login -->
<a href="/wp-login.php?action=login&login_type=support">Support Sign In</a>

<!-- Role Selection -->
<a href="/wp-login.php?action=login&login_type=select">Portal Login</a>
```

---

## 🎯 Common Tasks

### Create a Partner User

```php
$user = wp_create_user('john_doe', 'secure_password', 'john@company.com');
$user_obj = get_user_by('id', $user);
$user_obj->add_role('partner');
update_user_meta($user, 'lgp_account_active', '1');
```

### Create Support User (SSO)

```php
$user = wp_create_user('jane_support', wp_generate_password(), 'jane@company.com');
wp_update_user([
    'ID' => $user,
    'display_name' => 'Jane Support',
]);
$user_obj = get_user_by('id', $user);
$user_obj->add_role('support');
update_user_meta($user, 'lgp_sso_user', true);
update_user_meta($user, 'lgp_account_active', '1');
```

### Disable User Account

```php
// User can't login anymore
update_user_meta($user_id, 'lgp_account_active', '0');

// Re-enable user
update_user_meta($user_id, 'lgp_account_active', '1');
```

### Custom Redirect After Login

```php
add_filter('lgp_login_redirect', function($redirect, $fallback, $user_id) {
    $user = get_user_by('id', $user_id);
    
    if ($user->has_cap('lgp_partner')) {
        return home_url('/my-dashboard');
    }
    
    return $redirect;
}, 10, 3);
```

### Log Login Events

```php
// Successful login
add_action('lgp_login_success', function($user_id, $role) {
    $user = get_user_by('id', $user_id);
    error_log("User {$user->user_email} logged in as {$role}");
}, 10, 2);

// Failed login
add_action('lgp_login_failed', function($error_code, $username) {
    error_log("Failed login attempt for {$username}: {$error_code}");
}, 10, 2);
```

---

## ✅ Checklist

Before going live:

- [ ] Microsoft app registered in Azure
- [ ] Client ID and Secret added to wp-config.php
- [ ] Redirect URI configured correctly in Azure
- [ ] API permissions granted (openid, profile, email)
- [ ] Test Partner login works
- [ ] Test Support (SSO) login works
- [ ] Partner Dashboard page exists at /partner-dashboard
- [ ] Support Dashboard page exists at /support-dashboard
- [ ] Site uses HTTPS (required for SSO)
- [ ] Login links added to site navigation
- [ ] Users assigned correct roles (partner/support)
- [ ] Test users can log in
- [ ] Error messages appear correctly
- [ ] Session cookies work
- [ ] "Remember me" works for partners

---

## 🔍 Troubleshooting

### "Invalid username or password"
- Check username/password is correct
- Ensure user has "partner" role
- Check user account is active: `lgp_account_active != '0'`

### "Your account does not have access"
- User doesn't have required role
- Add role: `$user->add_role('partner')` or `add_role('support')`

### Microsoft login shows error
- Check Client ID is correct
- Check Client Secret is correct
- Verify Redirect URI matches exactly
- Ensure HTTPS is enabled
- Try in incognito mode

### "Invalid State Parameter"
- Server time not synchronized (sync NTP)
- Cookies disabled in browser
- CSRF token expired (try again)

### Users redirected to /wp-admin
- Dashboard pages don't exist
- Create `/partner-dashboard` and `/support-dashboard` pages
- Set page templates correctly

---

## 📚 Next Steps

1. **Read Full Documentation**: See `CUSTOM_LOGIN_SETUP.md`
2. **Explore Examples**: Check `tests/login-system-examples.php`
3. **Customize Styling**: Edit `assets/css/login-page.css`
4. **Customize Template**: Edit `templates/custom-login.php`
5. **Add Custom Logic**: Use filters and actions in your theme

---

## 🔗 Resources

- **Azure Portal**: https://portal.azure.com
- **WordPress Documentation**: https://developer.wordpress.org/plugins/
- **Microsoft Graph API**: https://docs.microsoft.com/en-us/graph/

---

## 💡 Pro Tips

1. **Use Constants**: Store secrets in wp-config.php, not database
2. **Test Often**: Test login flows regularly
3. **Monitor Logs**: Check `/wp-content/debug.log` for issues
4. **Backup Users**: Export user list before changes
5. **Document Changes**: Keep notes on customizations
6. **Use Roles**: Always use proper WordPress roles
7. **Audit Logins**: Monitor who logs in and when
8. **Update Regularly**: Keep WordPress and plugins updated

---

**Need Help?** Check the full documentation in `CUSTOM_LOGIN_SETUP.md`
