# LounGenie Portal - Optional Configuration Guide

**Quick Reference for Optional PHP Constants**

## Overview

The LounGenie Portal plugin works out-of-the-box without any configuration. However, you can enable advanced features by adding optional constants to your `wp-config.php` file.

---

## Microsoft Graph Email Integration

**Feature:** Use Microsoft Graph API for inbound/outbound email processing

**When to use:** If you want to process support emails through Microsoft 365/Outlook

**Setup:**

1. Create Azure App Registration at [Azure Portal](https://portal.azure.com)
2. Add these constants to `wp-config.php`:

```php
// Microsoft Graph API Configuration
define('LGP_AZURE_TENANT_ID', 'your-tenant-id-here');
define('LGP_AZURE_CLIENT_ID', 'your-client-id-here');
define('LGP_AZURE_CLIENT_SECRET', 'your-client-secret-here');
define('LGP_SHARED_MAILBOX', 'support@yourcompany.com');
```

**Alternative:** Configure via WordPress Admin → Settings → Email Integration (stores in database)

**Fallback:** Plugin falls back to environment variables or WordPress options if constants not defined

---

## Microsoft 365 Single Sign-On (SSO)

**Feature:** Allow users to log in with Microsoft 365 accounts

**When to use:** If your organization uses Microsoft 365 and you want SSO

**Setup:**

1. Create Azure App Registration with OAuth redirect URI
2. Add these constants to `wp-config.php`:

```php
// Microsoft 365 SSO Configuration
define('LGP_MICROSOFT_CLIENT_ID', 'your-sso-client-id-here');
define('LGP_MICROSOFT_CLIENT_SECRET', 'your-sso-client-secret-here');
define('LGP_MICROSOFT_TENANT_ID', 'your-tenant-id-here'); // Or 'common' for multi-tenant
```

**Alternative:** Configure via WordPress Admin → Settings → M365 SSO (stores in database)

**Fallback:** Plugin falls back to WordPress options if constants not defined

---

## Debug Mode

**Feature:** Enable verbose logging for troubleshooting

**When to use:** During development or when diagnosing issues

**Setup:**

Add this constant to `wp-config.php`:

```php
// Enable Debug Logging
define('LGP_DEBUG', true);
```

**Effect:** Logs detailed information to `wp-content/debug.log` (requires `WP_DEBUG_LOG` enabled)

**Warning:** ⚠️ Never enable in production (exposes sensitive data in logs)

---

## Email Pipeline Selector

**Feature:** Choose between new Microsoft Graph pipeline or legacy POP3 handler

**When to use:** During migration or if experiencing email handling issues

**Setup:**

Add this constant to `wp-config.php`:

```php
// Select Email Pipeline
define('LGP_EMAIL_PIPELINE', 'new');  // Use Microsoft Graph
// OR
define('LGP_EMAIL_PIPELINE', 'legacy'); // Use POP3 handler
```

**Values:**
- `'new'`, `true`, or `1` = Use Microsoft Graph API (requires Azure setup)
- `'legacy'`, `false`, or `0` = Use POP3 polling (legacy mode)
- Not defined = Defaults to legacy mode for backward compatibility

**Fallback:** Plugin checks environment variable `LGP_EMAIL_PIPELINE` if constant not defined

---

## Configuration Priority

The plugin checks for configuration in this order:

1. **PHP Constants** (defined in `wp-config.php`) ← Highest priority
2. **Environment Variables** (set in `.env` or server environment)
3. **WordPress Options** (configured via admin pages)
4. **Default Values** (safe fallbacks) ← Lowest priority

This means you can override admin settings by defining constants.

---

## Example Complete Configuration

Here's a complete example for `wp-config.php`:

```php
// LounGenie Portal - Optional Configuration

// Microsoft Graph API (for email processing)
define('LGP_AZURE_TENANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('LGP_AZURE_CLIENT_ID', 'yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy');
define('LGP_AZURE_CLIENT_SECRET', 'your-secret-value-here');
define('LGP_SHARED_MAILBOX', 'support@yourcompany.com');

// Microsoft 365 SSO (for user authentication)
define('LGP_MICROSOFT_CLIENT_ID', 'zzzzzzzz-zzzz-zzzz-zzzz-zzzzzzzzzzzz');
define('LGP_MICROSOFT_CLIENT_SECRET', 'your-sso-secret-here');
define('LGP_MICROSOFT_TENANT_ID', 'common'); // Or specific tenant ID

// Email Pipeline Selector
define('LGP_EMAIL_PIPELINE', 'new'); // Use Microsoft Graph

// Debug Mode (NEVER in production!)
// define('LGP_DEBUG', true);
```

---

## Environment Variables

You can also use environment variables instead of constants:

```bash
# .env file or server environment
export LGP_AZURE_TENANT_ID="your-tenant-id"
export LGP_AZURE_CLIENT_ID="your-client-id"
export LGP_AZURE_CLIENT_SECRET="your-secret"
export LGP_SHARED_MAILBOX="support@yourcompany.com"
export LGP_EMAIL_PIPELINE="new"
```

The plugin will read these automatically via `getenv()`.

---

## Troubleshooting

### "Microsoft Graph not working"

**Check:**
1. Are constants defined in `wp-config.php`?
2. Are Azure credentials correct?
3. Is Azure app properly configured with API permissions?
4. Check `wp-content/debug.log` for errors

**Test:**
```bash
php -r "var_dump(getenv('LGP_AZURE_CLIENT_ID'));"
```

### "SSO button not appearing"

**Check:**
1. Are SSO constants defined?
2. Is `LGP_Microsoft_SSO` class loaded?
3. Check browser console for JavaScript errors

### "Email handler using wrong pipeline"

**Check:**
```bash
# Check if constant is defined
php -r "var_dump(defined('LGP_EMAIL_PIPELINE'));"

# Check environment variable
php -r "var_dump(getenv('LGP_EMAIL_PIPELINE'));"
```

---

## Security Best Practices

### ✅ DO:
- Store sensitive secrets in `wp-config.php` (not tracked by Git)
- Use environment variables on production servers
- Rotate client secrets regularly
- Use specific tenant IDs (not 'common') when possible

### ❌ DON'T:
- Commit secrets to version control
- Enable `LGP_DEBUG` in production
- Share client secrets in documentation
- Use admin-configured options for multi-site deployments

---

## WordPress Admin Configuration

Alternatively, you can configure most settings via WordPress admin pages:

- **Microsoft Graph:** Settings → Email Integration
- **Microsoft SSO:** Settings → M365 SSO
- **HubSpot CRM:** Settings → HubSpot Integration
- **Outlook Email:** Settings → Outlook Integration

**Note:** PHP constants (in `wp-config.php`) will override admin settings.

---

## Summary

| Feature | Required? | Constant | Default | Priority |
|---------|-----------|----------|---------|----------|
| Microsoft Graph | No | `LGP_AZURE_*` | Disabled | Optional |
| Microsoft SSO | No | `LGP_MICROSOFT_*` | Disabled | Optional |
| Debug Mode | No | `LGP_DEBUG` | `false` | Development |
| Email Pipeline | No | `LGP_EMAIL_PIPELINE` | `'legacy'` | Optional |

**All features work without any constants defined.** Constants are for advanced users who want:
- Environment-specific configuration
- Security via `wp-config.php` (not database)
- Override admin settings
- Multi-environment deployments

---

**Last Updated:** December 22, 2024  
**Plugin Version:** 1.8.1  
**Documentation:** See [ENTERPRISE_FEATURES.md](ENTERPRISE_FEATURES.md) for detailed setup guides
