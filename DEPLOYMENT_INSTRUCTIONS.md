# 🚀 LounGenie Portal - Deployment Instructions

**Package:** `loungenie-portal-deployment.zip`  
**Size:** 108KB compressed / 916KB uncompressed  
**Date:** December 17, 2024

---

## 📦 Package Contents

✅ **Included (Production Files):**
- ✅ Core plugin PHP files (`loungenie-portal.php`, `uninstall.php`)
- ✅ Optimized CSS (56KB - design-tokens.css + portal.css)
- ✅ JavaScript files (portal.js, gateway-view.js, training-view.js, etc.)
- ✅ Templates (dashboard, company profile, gateway view, map view, etc.)
- ✅ API endpoints (tickets, units, companies, gateways, etc.)
- ✅ Roles configuration (partner, support)
- ✅ Classes (auth, database, security, Microsoft SSO, etc.)
- ✅ WordPress admin integration
- ✅ WP-CLI commands

❌ **Excluded (Development Only):**
- ❌ `node_modules/` (60MB) - npm dev dependencies
- ❌ `vendor/` (26MB) - Composer dev tools (PHPUnit, CodeSniffer)
- ❌ `tests/` - PHPUnit test suite
- ❌ `scripts/` - Offline development scripts
- ❌ `.md` documentation files
- ❌ `package.json`, `composer.json` - dependency configs
- ❌ `phpunit.xml`, `phpcs.xml` - testing configs

---

## 🔧 Installation Steps

### Option 1: WordPress Admin Upload

1. **Download** `loungenie-portal-deployment.zip` to your local machine

2. **Login** to WordPress Admin
   - Go to: `https://yourdomain.com/wp-admin`

3. **Navigate** to Plugins
   - Click: **Plugins** → **Add New** → **Upload Plugin**

4. **Upload** the zip file
   - Click **Choose File** → Select `loungenie-portal-deployment.zip`
   - Click **Install Now**

5. **Activate** the plugin
   - Click **Activate Plugin**

6. **Configure** Microsoft SSO
   - Go to: **Settings** → **LounGenie Portal**
   - Enter Azure AD credentials (see [AZURE_AD_SETUP.md](AZURE_AD_SETUP.md))

### Option 2: FTP/SFTP Upload

1. **Extract** the zip file locally
   ```bash
   unzip loungenie-portal-deployment.zip
   ```

2. **Upload** via FTP/SFTP
   - Upload the entire `loungenie-portal/` folder to:
   - `wp-content/plugins/loungenie-portal/`

3. **Set Permissions**
   ```bash
   chmod 755 wp-content/plugins/loungenie-portal/
   chmod 644 wp-content/plugins/loungenie-portal/*.php
   ```

4. **Activate** via WordPress Admin
   - Go to: **Plugins** → Find **LounGenie Portal** → Click **Activate**

### Option 3: WP-CLI Installation

```bash
# Upload zip to server first, then:
wp plugin install /path/to/loungenie-portal-deployment.zip --activate

# Or if already uploaded to plugins directory:
wp plugin activate loungenie-portal
```

---

## ⚙️ Post-Installation Configuration

### 1. Database Setup ✅
The plugin will **automatically create** required tables on activation:
- `lgp_companies`
- `lgp_units`
- `lgp_gateways`
- `lgp_tickets`
- `lgp_service_notes`
- `lgp_training_videos`
- And more...

### 2. Microsoft Azure AD SSO Setup 🔐
**Required for user authentication:**

1. Go to: **Settings** → **LounGenie Portal** → **Microsoft SSO**
2. Enter your Azure AD credentials:
   - Application (Client) ID
   - Directory (Tenant) ID
   - Client Secret
   - Redirect URI: `https://yourdomain.com/psp-azure-callback`

**See:** [AZURE_AD_SETUP.md](AZURE_AD_SETUP.md) for detailed setup

### 3. API Integrations (Optional) 🔌

**HubSpot:**
- Settings → LounGenie Portal → HubSpot API Key
- Used for CRM sync and lead management

**Outlook Calendar:**
- Settings → LounGenie Portal → Outlook Integration
- Used for service appointment scheduling

### 4. User Roles Setup 👥

The plugin creates custom roles:
- **Partner** - Property managers/owners (view their units/gateways)
- **Support** - Support technicians (view all, manage tickets)

**Assign roles:**
- Users → Select user → Change role to "Partner" or "Support"

---

## 🧪 Verify Installation

### 1. Check Plugin Status
- Go to: **Plugins** → Verify "LounGenie Portal" is **Active**

### 2. Test Portal Access
- Navigate to: `https://yourdomain.com/portal`
- Should redirect to Microsoft login if not authenticated

### 3. Check System Health
- Go to: **Tools** → **LounGenie System Health**
- Verify all checks pass ✅

### 4. Test Database
```sql
-- Check tables exist
SHOW TABLES LIKE 'lgp_%';

-- Should return 10+ tables
```

### 5. Verify CSS/JS Loading
- Open browser DevTools → Network tab
- Load portal page
- Verify:
  - `design-tokens.css` loads (27KB)
  - `portal.css` loads (29KB)
  - `portal.js` loads
  - FontAwesome 6.5.1 loads from CDN

---

## 🔒 Security Checklist

Before going live, verify:

- ✅ HTTPS enabled (SSL certificate installed)
- ✅ WordPress updated to latest version
- ✅ PHP 7.4+ or 8.0+ running
- ✅ File permissions set correctly (755/644)
- ✅ Azure AD redirect URI matches exactly
- ✅ Database credentials secured
- ✅ WP Debug mode OFF (`define('WP_DEBUG', false);`)
- ✅ Error logging enabled but not visible to users

---

## 📊 Performance Optimization

### Recommended Settings

**PHP:**
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
```

**WordPress:**
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**Caching:**
- Enable object caching (Redis/Memcached)
- Use CDN for static assets
- Enable gzip compression

---

## 🐛 Troubleshooting

### Plugin won't activate
**Error:** "Plugin could not be activated"

**Solution:**
1. Check PHP version: `php -v` (must be 7.4+)
2. Check error logs: `wp-content/debug.log`
3. Verify file permissions: `chmod 755 loungenie-portal/`

### CSS not loading / Design looks broken
**Symptoms:** Portal appears unstyled, colors wrong

**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Regenerate CSS: Settings → LounGenie Portal → Clear Cache
3. Check file exists: `wp-content/plugins/loungenie-portal/assets/css/design-tokens.css`

### Microsoft SSO not working
**Error:** "Redirect URI mismatch"

**Solution:**
1. Azure Portal: Verify redirect URI is **exactly**: `https://yourdomain.com/psp-azure-callback`
2. No trailing slash
3. HTTPS required
4. See [AZURE_AD_SETUP.md](AZURE_AD_SETUP.md)

### Database tables not created
**Error:** "Table 'lgp_companies' doesn't exist"

**Solution:**
```bash
# Deactivate and reactivate plugin
wp plugin deactivate loungenie-portal
wp plugin activate loungenie-portal

# Or manually run:
wp eval-file wp-content/plugins/loungenie-portal/includes/class-lgp-database.php
```

### Portal returns 404
**Error:** Page not found

**Solution:**
1. Flush rewrite rules: Settings → Permalinks → **Save Changes**
2. Or via WP-CLI: `wp rewrite flush`

---

## 📁 File Structure

```
loungenie-portal/
├── loungenie-portal.php      # Main plugin file
├── uninstall.php              # Cleanup on uninstall
├── api/                       # REST API endpoints
│   ├── tickets.php
│   ├── units.php
│   ├── companies.php
│   └── ...
├── assets/                    # Frontend assets
│   ├── css/
│   │   ├── design-tokens.css  # Design system (27KB)
│   │   └── portal.css         # Portal styles (29KB)
│   └── js/
│       ├── portal.js          # Core JavaScript
│       ├── gateway-view.js
│       └── ...
├── includes/                  # PHP classes
│   ├── class-lgp-auth.php
│   ├── class-lgp-database.php
│   ├── class-lgp-microsoft-sso.php
│   └── ...
├── templates/                 # View templates
│   ├── dashboard-partner.php
│   ├── dashboard-support.php
│   ├── company-profile.php
│   └── ...
├── roles/                     # Custom role definitions
│   ├── partner.php
│   └── support.php
├── wp-admin/                  # Admin integration
└── wp-cli/                    # WP-CLI commands
```

---

## 🔄 Updates

### Manual Update Process

1. **Backup** current plugin folder
2. **Deactivate** plugin (don't delete)
3. **Replace** files with new version
4. **Reactivate** plugin
5. **Test** functionality

### Automatic Updates (Future)

Plugin can be updated via WordPress.org plugin repository or custom update server.

---

## 📚 Documentation

**Full Documentation:**
- [AZURE_AD_SETUP.md](AZURE_AD_SETUP.md) - Microsoft SSO configuration
- [COMPREHENSIVE_DESIGN_GUIDE.md](COMPREHENSIVE_DESIGN_GUIDE.md) - Design system reference
- [CSS_OPTIMIZATION_RESULTS.md](CSS_OPTIMIZATION_RESULTS.md) - Performance details
- [WORDPRESS_SSO_SETUP.md](WORDPRESS_SSO_SETUP.md) - WordPress integration

**API Documentation:**
- REST API: `https://yourdomain.com/wp-json/lgp/v1/`
- Endpoints: `/tickets`, `/units`, `/companies`, `/gateways`, etc.

---

## ✅ Production Checklist

Before deploying to production:

- [ ] SSL certificate installed and HTTPS enabled
- [ ] Azure AD app registered and configured
- [ ] Redirect URI set correctly in Azure Portal
- [ ] WordPress permalinks set to "Post name"
- [ ] Database tables created successfully
- [ ] File permissions set (755/644)
- [ ] WP_DEBUG turned off
- [ ] Error logging enabled
- [ ] Caching configured (Redis/Object cache)
- [ ] CDN configured (optional but recommended)
- [ ] Backup system in place
- [ ] Test user accounts created (Partner + Support roles)
- [ ] Test portal login and functionality
- [ ] Test API endpoints
- [ ] Verify CSS/JS loading correctly
- [ ] Mobile responsive design tested
- [ ] Browser compatibility tested (Chrome, Firefox, Safari, Edge)
- [ ] Performance test passed (Lighthouse/GTmetrix)

---

## 🆘 Support

**Issues?** Check:
1. WordPress error logs: `wp-content/debug.log`
2. PHP error logs: `/var/log/apache2/error.log` or similar
3. Browser console (F12) for JavaScript errors
4. System Health page: Tools → LounGenie System Health

**Contact:**
- Technical documentation in this repo
- Check [GitHub Issues](https://github.com/faith233525/Pool-Safe-Portal/issues)

---

## 📈 Version Information

**Package Version:** 1.0.0  
**WordPress Compatibility:** 5.8+  
**PHP Compatibility:** 7.4+  
**Tested Up To:** WordPress 6.4  

**Features:**
- ✅ Microsoft Azure AD SSO
- ✅ Partner & Support Dashboards
- ✅ Gateway Management
- ✅ Service Ticket System
- ✅ Training Video Library
- ✅ Company Profile Management
- ✅ Interactive Map View
- ✅ Mobile Responsive Design
- ✅ Modern Design System (Teal/Cyan)
- ✅ REST API
- ✅ WP-CLI Commands

---

**Generated:** December 17, 2024  
**Package:** loungenie-portal-deployment.zip (108KB)  
**Status:** ✅ Ready for Production Deployment
