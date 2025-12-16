# Azure AD Configuration for Microsoft 365 SSO

This guide provides step-by-step instructions for configuring Azure Active Directory to enable Microsoft 365 Single Sign-On (SSO) for the PoolSafe Portal.

## Prerequisites

- Azure subscription with Azure Active Directory
- Administrative access to Azure Portal
- The WordPress site URL where the portal is deployed

## Configuration Steps

### 1. Access Azure Portal

1. Navigate to [Azure Portal](https://portal.azure.com)
2. Sign in with your administrative credentials
3. Navigate to **Azure Active Directory** from the left sidebar

### 2. Register Application

1. In Azure AD, click **App registrations** in the left menu
2. Click **+ New registration**
3. Configure the application:
   - **Name**: `PoolSafe Portal` (or your preferred name)
   - **Supported account types**: Select `Accounts in this organizational directory only (Single tenant)`
   - **Redirect URI**: 
     - Platform: `Web`
     - URI: `https://your-site.com/wp-admin/admin-ajax.php?action=psp_support_callback`
     - Replace `your-site.com` with your actual WordPress site domain
4. Click **Register**

### 3. Note Application IDs

After registration, you'll be taken to the app overview page. **Copy and save** these values:

- **Application (client) ID**: Example: `1e57c611-e11d-46ec-9a88-63ef012186c3`
- **Directory (tenant) ID**: Example: `2dad1f4c-0cda-47ba-88a9-3b7d4a7aec83`

You will need these values for WordPress configuration.

### 4. Create Client Secret

1. In your app registration, click **Certificates & secrets** from the left menu
2. Click **+ New client secret**
3. Configure the secret:
   - **Description**: `PoolSafe Portal Secret`
   - **Expires**: Choose appropriate expiration (recommended: 24 months)
4. Click **Add**
5. **Immediately copy the secret Value** - it will only be shown once!
   - Example format: `abc123def456~ghi789jkl012.mno345pqr678`
6. Store this securely - you'll need it for WordPress configuration

⚠️ **Important**: The client secret value is only displayed once. If you lose it, you'll need to create a new secret.

### 5. Configure API Permissions

1. Click **API permissions** from the left menu
2. Click **+ Add a permission**
3. Select **Microsoft Graph**
4. Select **Delegated permissions**
5. Search for and add these permissions:
   - `User.Read` - Read user profile information
   - `email` - View users' email address
   - `openid` - Sign users in
   - `profile` - View users' basic profile
6. Click **Add permissions**
7. Click **Grant admin consent for [Your Organization]**
8. Confirm by clicking **Yes**

### 6. Configure Authentication Settings

1. Click **Authentication** from the left menu
2. Under **Platform configurations**, click on your Web platform redirect URI
3. Configure these settings:
   - **Access tokens**: ☑ (checked)
   - **ID tokens**: ☑ (checked)
4. Under **Advanced settings**:
   - **Allow public client flows**: ☐ (unchecked)
   - **Enable the following mobile and desktop flows**: ☐ (unchecked)
5. Under **Supported account types**, ensure:
   - **Accounts in this organizational directory only** is selected
6. Click **Save**

### 7. Optional: Configure Branding

1. Click **Branding & properties** from the left menu
2. Optionally add:
   - **Logo**: Your company logo (240x240px PNG)
   - **Home page URL**: Your portal URL
   - **Terms of service URL**: Your terms URL
   - **Privacy statement URL**: Your privacy policy URL
3. Click **Save**

## Redirect URI Formats

The redirect URI must exactly match the WordPress configuration:

### Production
```
https://portal.loungenie.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

### Staging
```
https://staging.yourdomain.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

### Development
```
https://dev.yourdomain.com/wp-admin/admin-ajax.php?action=psp_support_callback
```

⚠️ **Important**: 
- The URI must use HTTPS (not HTTP) in production
- The action parameter must be exactly `psp_support_callback`
- The URI must match exactly (case-sensitive)

## Security Considerations

1. **Client Secret Rotation**: Set a reminder to rotate client secrets before expiration
2. **Admin Consent**: Only administrators should grant consent for API permissions
3. **Least Privilege**: Only request the minimum permissions needed (User.Read, email, openid, profile)
4. **Access Review**: Regularly review who has access to the Azure AD app registration
5. **Monitor Sign-ins**: Use Azure AD sign-in logs to monitor authentication activity

## Verification

After configuration, you can verify the setup:

1. Go to **App registrations** → Your app → **Overview**
2. Verify:
   - ✅ Redirect URIs are configured
   - ✅ Client secret is created (not expired)
   - ✅ API permissions are granted
   - ✅ Application is enabled

## Troubleshooting

### "Reply URL does not match"
- Verify redirect URI exactly matches in both Azure AD and WordPress
- Check for trailing slashes or missing query parameters
- Ensure HTTPS is used (not HTTP)

### "AADSTS50105: The signed in user is not assigned to a role"
- Add users to the application in Azure AD
- Go to **Enterprise applications** → Your app → **Users and groups**
- Click **+ Add user/group** and assign users

### "AADSTS65001: The user or administrator has not consented"
- Grant admin consent for API permissions
- Go to **API permissions** → **Grant admin consent**

### Client secret expired
- Create a new client secret
- Update the secret in WordPress settings
- Delete the old secret in Azure AD

## Next Steps

After completing Azure AD configuration, proceed to [WordPress SSO Configuration](./WORDPRESS_SSO_SETUP.md) to complete the integration.

## Additional Resources

- [Azure AD App Registration Documentation](https://docs.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app)
- [Microsoft Graph Permissions Reference](https://docs.microsoft.com/en-us/graph/permissions-reference)
- [OAuth 2.0 Authorization Code Flow](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)
