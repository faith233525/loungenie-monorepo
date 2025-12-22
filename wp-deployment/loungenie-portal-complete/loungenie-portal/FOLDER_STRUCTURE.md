# LounGenie Portal - Folder Structure

## 📁 Complete Plugin Organization

```
loungenie-portal/
├── api/                              # REST API Endpoints
│   ├── attachments.php
│   ├── audit-log.php
│   ├── companies.php
│   ├── gateways.php
│   ├── service-notes.php
│   ├── tickets.php
│   ├── training-videos.php
│   └── units.php
│
├── assets/                            # Static Assets
│   ├── css/
│   │   ├── design-tokens.css         # ✨ 60-30-10 Color System
│   │   ├── portal-components.css     # ✨ NEW Component Library
│   │   ├── design-system-refactored.css
│   │   ├── attachments.css
│   │   ├── login.css
│   │   └── portal.css
│   ├── js/
│   │   ├── portal.js
│   │   ├── portal-init.js
│   │   ├── lgp-utils.js
│   │   ├── company-profile-enhancements.js
│   │   ├── company-profile-partner-polish.js
│   │   ├── training-view.js
│   │   └── attachments.js
│   └── tests/                        # JavaScript Tests
│       └── ...
│
├── includes/                          # Core Plugin Classes
│   ├── class-lgp-assets.php          # ✅ Asset Loading
│   ├── class-lgp-auth.php            # ✅ Namespaced Auth
│   ├── class-lgp-cache.php           # Performance Caching
│   ├── class-lgp-capabilities.php    # Role Capabilities
│   ├── class-lgp-database.php        # Database Schema
│   ├── class-lgp-email-handler.php
│   ├── class-lgp-email-to-ticket.php
│   ├── class-lgp-file-validator.php
│   ├── class-lgp-gateway.php
│   ├── class-lgp-geocode.php
│   ├── class-lgp-hubspot.php
│   ├── class-lgp-loader.php
│   ├── class-lgp-logger.php
│   ├── class-lgp-microsoft-sso.php
│   ├── class-lgp-migrations.php
│   ├── class-lgp-notifications.php
│   ├── class-lgp-outlook.php
│   ├── class-lgp-rate-limiter.php
│   ├── class-lgp-rest-errors.php
│   ├── class-lgp-router.php
│   ├── class-lgp-security.php
│   ├── class-lgp-shared-hosting-rules.php
│   ├── class-lgp-system-health.php
│   ├── class-lgp-training-video.php
│   └── class-lgp-attachments.php
│
├── languages/                         # ✨ NEW Internationalization
│   └── loungenie-portal.pot          # Translation Template
│
├── roles/                             # Custom User Roles
│   ├── partner.php
│   └── support.php
│
├── scripts/                           # Utility Scripts
│   ├── offline-run.php
│   ├── OfflineBootstrap.php
│   ├── OfflineDataSeeder.php
│   ├── OfflineHelpers.php
│   └── offline-data/
│
├── templates/                         # UI Templates
│   ├── components/
│   ├── company-profile.php
│   ├── dashboard-partner.php          # ✅ Updated Structure
│   ├── dashboard-support.php
│   ├── gateway-view.php
│   ├── map-view.php
│   ├── partner-login.php
│   ├── portal-login.php
│   ├── portal-shell.php               # ✅ Role Theming Added
│   ├── support-login.php
│   ├── tickets-view.php
│   ├── training-view.php
│   └── units-view.php
│
├── tests/                             # PHPUnit Tests
│   └── ...
│
├── vendor/                            # Composer Dependencies (optional for production)
│
├── wp-admin/                          # WordPress Admin Customization
│
├── wp-cli/                            # WordPress CLI Commands
│
├── .gitignore                         # ✅ Updated
├── composer.json
├── package.json
├── phpcs.xml
├── phpunit.xml
├── loungenie-portal.php               # ✅ Main Plugin File
├── uninstall.php
├── CHANGELOG.md
├── CONTRIBUTING.md
├── ENTERPRISE_FEATURES.md
├── FILTERING_GUIDE.md
├── IMPLEMENTATION_SUMMARY.md
├── IMPLEMENTATION_UPDATES.md          # ✨ NEW Detailed Updates
├── OFFLINE_DEVELOPMENT.md
├── OFFLINE_SUITE_SUMMARY.md
├── README.md
├── SETUP_GUIDE.md
├── WPCS_STRATEGY.md
├── VERSION
├── FOLDER_STRUCTURE.md                # ✨ NEW This File
└── sample-data.sql
```

## ✨ Key Improvements

### Design System (Colors Preserved)
- **60-30-10 Color Rule**: Implemented in `design-tokens.css`
  - 60% Atmosphere: #E9F8F9 (Soft Cyan) + #FFFFFF (White)
  - 30% Structure: #0F172A (Deep Navy)
  - 10% Action: #3AA6B9 (Teal) + #25D0EE (Cyan)

### New Files
- ✨ `design-tokens.css` - Complete design token system
- ✨ `portal-components.css` - Modern component library
- ✨ `languages/` - Directory for internationalization
- ✨ `IMPLEMENTATION_UPDATES.md` - Detailed update documentation

### Updated Files
- ✅ `loungenie-portal.php` - i18n support
- ✅ `class-lgp-auth.php` - PHP namespacing
- ✅ `class-lgp-assets.php` - New CSS loading
- ✅ `portal-shell.php` - Role theming + accessibility
- ✅ `dashboard-partner.php` - Modern structure

## 📦 For Production Deployment

When creating a production ZIP, exclude:
```
vendor/              (optional - use composer install instead)
node_modules/        (not needed for production)
tests/               (development only)
.phpunit.result.cache (development only)
```

Recommended production structure:
```
loungenie-portal/
├── api/
├── assets/           (NO tests subdirectory)
├── includes/
├── languages/
├── roles/
├── scripts/
├── templates/
├── wp-admin/
├── wp-cli/
├── loungenie-portal.php
├── uninstall.php
├── [all documentation]
└── composer.json     (for installation of dependencies)
```

## 🎨 Color Token Reference

All colors are now properly defined in `design-tokens.css`:

### Atmosphere (60%)
```css
--lgp-atmosphere-primary: #E9F8F9;  /* Main background */
--lgp-atmosphere-white: #FFFFFF;    /* Cards */
--lgp-atmosphere-alt: #F5FBFC;      /* Alternating bg */
--lgp-atmosphere-border: #D8E9EC;   /* Borders */
--lgp-atmosphere-hover: #EEF7F9;    /* Hover states */
```

### Structure (30%)
```css
--lgp-structure-navy: #0F172A;      /* Primary text */
--lgp-structure-headline: #0F172A;  /* Headings */
--lgp-structure-body: #454F5E;      /* Body text */
--lgp-structure-secondary: #7A8699; /* Secondary text */
--lgp-structure-tertiary: #94A3B8;  /* Tertiary text */
```

### Action (10%)
```css
--lgp-action-teal: #3AA6B9;         /* Partner primary */
--lgp-action-teal-dark: #2A8A9A;    /* Partner hover */
--lgp-action-teal-light: #D8EFF3;   /* Partner badge */
--lgp-action-cyan: #25D0EE;         /* Support primary */
--lgp-action-cyan-dark: #1AB9D4;    /* Support hover */
--lgp-action-cyan-light: #D6F6FC;   /* Support badge */
```

### Status Colors
```css
--lgp-success: #16A34A;
--lgp-warning: #D97706;
--lgp-danger: #DC2626;
--lgp-info: #0D9488;
```

## 🚀 Development Workflow

1. Clone/Pull the repository
2. Run: `composer install` (loads vendor dependencies)
3. Run: `npm install` (loads dev dependencies)
4. Develop in the single `loungenie-portal` folder
5. When deploying:
   - Remove `vendor/` and `node_modules/`
   - Run `composer install --no-dev` on production
   - ZIP the clean folder

## ✅ Status

- **Single Plugin Folder**: ✅ loungenie-portal/
- **No Duplicates**: ✅ Removed loungenie-portal-deploy
- **No Old Scan Data**: ✅ Removed .scan folder
- **No Unused ZIPs**: ✅ Removed .zip files
- **Colors Preserved**: ✅ 60-30-10 system intact
- **All Improvements**: ✅ Included and documented

