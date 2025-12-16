# WordPress Microsoft 365 SSO Configuration

This guide explains how to configure WordPress to enable Microsoft 365 Single Sign-On (SSO) for support users in the PoolSafe Portal.

## Prerequisites

- WordPress site with PoolSafe Portal plugin installed and activated
- Azure AD application configured (see [AZURE_AD_SETUP.md](./AZURE_AD_SETUP.md))
- Administrative access to WordPress
- The following values from Azure AD:
  - Client ID
  - Tenant ID  
  - Client Secret

## Configuration Steps

### 1. Access WordPress Admin

1. Log in to your WordPress admin dashboard
2. Navigate to **Companies** → **M365 Settings** in the left sidebar
   - If you don't see this menu, ensure the PoolSafe Portal plugin is activated

### 2. Enter Azure AD Credentials

On the M365 Settings page, enter the values you obtained from Azure AD:

#### Client ID
- **Field**: Client ID
- **Value**: Your Azure AD Application (client) ID
- **Example**: `1e57c611-e11d-46ec-9a88-63ef012186c3`
- **Where to find**: Azure Portal → App registrations → Your app → Overview

#### Tenant ID
- **Field**: Tenant ID
- **Value**: Your Azure AD Directory (tenant) ID
- **Example**: `2dad1f4c-0cda-47ba-88a9-3b7d4a7aec83`
- **Where to find**: Azure Portal → App registrations → Your app → Overview

#### Client Secret
- **Field**: Client Secret
- **Value**: The secret value you copied when creating the client secret
- **Example**: `abc123def456~ghi789jkl012.mno345pqr678`
- **Where to find**: Azure Portal → App registrations → Your app → Certificates & secrets

⚠️ **Security Note**: Client secrets are sensitive credentials. Never share them or commit them to version control.

### 3. Configure OAuth Callback

The OAuth callback is automatically configured by the plugin:

- **Callback Action**: `psp_support_callback`
- **Full URL**: `https://your-site.com/wp-admin/admin-ajax.php?action=psp_support_callback`

This URL must match the redirect URI configured in Azure AD exactly.

### 4. Save Settings

1. Review all entered values for accuracy
2. Click **Save Changes**
3. You should see a success message: "M365 SSO settings saved successfully"

### 5. Verify Database Storage

The settings are stored in the WordPress options table with these keys:

```sql
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name IN (
    'psp_m365_client_id',
    'psp_m365_tenant_id', 
    'psp_m365_client_secret'
);
```

⚠️ **Security**: The client secret is stored encrypted in the database.

## Testing the Configuration

### Test Support User Login

1. **Logout** of WordPress admin (or use an incognito/private browser window)
2. Navigate to your portal page: `https://your-site.com/portal`
3. Click **"Sign in with Microsoft"** button
4. You should be redirected to Microsoft login page
5. Sign in with your Microsoft 365 credentials
6. After successful authentication, you should be redirected back to the portal
7. Verify you're logged in as a Support user

### Expected Login Flow

```
Portal Page → Click "Sign in with Microsoft" 
  → Redirect to Microsoft login
  → Enter Microsoft 365 credentials
  → Microsoft redirects back to WordPress callback
  → Plugin validates token and creates/updates user
  → User assigned Support role
  → Redirect to portal dashboard
```

### Verify Support Role Assignment

After logging in with Microsoft 365:

1. Go to WordPress Admin → **Users**
2. Find your user account
3. Verify:
   - ✅ User exists in WordPress
   - ✅ Role is set to "Support"
   - ✅ Email matches your Microsoft 365 email

## Role-Based Access

### Support Users (M365 SSO)
Support users who authenticate via Microsoft 365 SSO have access to:

- ✅ View all companies and management companies
- ✅ View all LounGenie units
- ✅ Track installs, service, maintenance, updates
- ✅ View all tickets
- ✅ View partner locations on map
- ✅ Full dashboard access
- ✅ Filter, search, sort all data
- ✅ Admin interface for company management

### Partner Users (Company Login)
Partners who authenticate with company credentials see only:

- ✅ Their own company data
- ✅ Their management company
- ✅ Their LounGenie unit count
- ✅ Submit service/install/update requests
- ✅ Track their request status and history
- ❌ Cannot see other companies' data
- ❌ No admin interface access

## Security Considerations

### Token Validation

The plugin validates Microsoft 365 tokens by:

1. Verifying the token signature using Microsoft's public keys
2. Checking the token expiration time
3. Validating the audience (client ID)
4. Validating the issuer (Microsoft)
5. Checking the tenant ID

### Session Management

- Sessions are stored in `wp_psp_sessions` table
- Session tokens are hashed with SHA-256
- Sessions expire after 7 days of inactivity
- Users can be logged out programmatically or manually

### User Provisioning

When a user logs in with Microsoft 365 for the first time:

1. Plugin checks if a WordPress user exists with that email
2. If not, creates a new WordPress user
3. Assigns the "Support" role
4. Stores the Microsoft 365 user ID for future logins
5. Updates user profile information from Microsoft Graph API

## Troubleshooting

### "Invalid Client Secret"

**Symptoms**: Login fails with error about invalid credentials

**Solutions**:
- Verify the client secret is copied correctly (no extra spaces)
- Check if the client secret has expired in Azure AD
- Create a new client secret and update WordPress settings

### "Redirect URI Mismatch"

**Symptoms**: Error message saying redirect URI doesn't match

**Solutions**:
- Verify the redirect URI in Azure AD exactly matches: `https://your-site.com/wp-admin/admin-ajax.php?action=psp_support_callback`
- Check for typos, extra slashes, or HTTP vs HTTPS
- Ensure the domain matches exactly (no www vs non-www mismatches)

### "User Not Assigned to Role"

**Symptoms**: Error AADSTS50105

**Solutions**:
- Go to Azure Portal → Enterprise applications → Your app
- Click **Users and groups**
- Click **+ Add user/group**
- Assign the user to the application

### Login Button Not Appearing

**Symptoms**: "Sign in with Microsoft" button doesn't show

**Solutions**:
- Verify plugin is activated
- Check that M365 settings are saved with all three values
- Clear WordPress cache and browser cache
- Check browser console for JavaScript errors

### User Assigned Wrong Role

**Symptoms**: M365 user gets Partner role instead of Support

**Solutions**:
- The plugin should automatically assign Support role to M365 users
- Check the user's role in WordPress Admin → Users
- Manually update the role if needed
- Check plugin code for role assignment logic

### Sessions Not Persisting

**Symptoms**: User gets logged out immediately or after page refresh

**Solutions**:
- Check `wp_psp_sessions` table exists
- Verify database has proper permissions
- Check for caching plugins interfering with sessions
- Review browser cookie settings (third-party cookies)

## Environment-Specific Configuration

### Production

```
Client ID: [Production Azure AD App Client ID]
Tenant ID: [Production Azure AD App Tenant ID]
Client Secret: [Production Azure AD App Client Secret]
Redirect URI: https://portal.loungenie.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

### Staging

```
Client ID: [Staging Azure AD App Client ID]
Tenant ID: [Staging Azure AD App Tenant ID]
Client Secret: [Staging Azure AD App Client Secret]
Redirect URI: https://staging.yourdomain.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

### Development

```
Client ID: [Development Azure AD App Client ID]
Tenant ID: [Development Azure AD App Tenant ID]
Client Secret: [Development Azure AD App Client Secret]
Redirect URI: https://dev.yourdomain.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

⚠️ **Important**: Each environment should have its own Azure AD app registration with appropriate redirect URIs.

## Monitoring and Maintenance

### Login Activity Monitoring

1. Navigate to **Companies** → **Login Activity** in WordPress Admin
2. Review the authentication logs for:
   - Successful M365 logins
   - Failed login attempts
   - User IP addresses
   - Timestamps
3. The plugin logs up to 100 recent authentication events

### Client Secret Rotation

Client secrets expire after the period set in Azure AD (e.g., 24 months):

1. **Before expiration**:
   - Create a new client secret in Azure AD
   - Update WordPress settings with new secret
   - Test login with new secret
   - Delete old secret in Azure AD after verification

2. **Set reminders**:
   - Set a calendar reminder 30 days before expiration
   - Document the expiration date in your runbook

### Regular Audits

Perform quarterly security audits:

- [ ] Review active Support users
- [ ] Check for expired sessions in `wp_psp_sessions` table
- [ ] Review login activity logs
- [ ] Verify Azure AD permissions haven't changed
- [ ] Test the login flow end-to-end
- [ ] Check client secret expiration dates

## API Integration

The M365 SSO uses these Microsoft Graph API endpoints:

### During Authentication
- `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize` - Initiate OAuth flow
- `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token` - Exchange authorization code for token

### User Information
- `https://graph.microsoft.com/v1.0/me` - Get user profile information

### Permissions Required
- `User.Read` - Read user profile
- `email` - Access user email
- `openid` - OpenID Connect authentication
- `profile` - Basic profile information

## Support and Resources

### Documentation
- [Azure AD Setup Guide](./AZURE_AD_SETUP.md)
- [Plugin README](./README.md)
- [Deployment Guide](./DEPLOYMENT_GUIDE.md)

### Microsoft Resources
- [Microsoft Graph API Documentation](https://docs.microsoft.com/en-us/graph/)
- [Azure AD OAuth 2.0 Flow](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)
- [Microsoft Identity Platform](https://docs.microsoft.com/en-us/azure/active-directory/develop/)

## Next Steps

After completing WordPress SSO configuration:

1. ✅ Test the login flow thoroughly
2. ✅ Assign Support role to appropriate M365 users
3. ✅ Import sample data for testing (see [Sample Data Import](./SAMPLE_DATA_IMPORT.md))
4. ✅ Configure company accounts for partner login
5. ✅ Review and test all portal features
6. ✅ Set up monitoring and alerting
7. ✅ Document production credentials securely
