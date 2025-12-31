# LounGenie Portal

**Enterprise SaaS Partner Management Portal**

![Status](https://img.shields.io/badge/status-production%20ready-brightgreen)
![Version](https://img.shields.io/badge/version-1.8.1-blue)
![License](https://img.shields.io/badge/license-GPLv2-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8+-green)
![PHP](https://img.shields.io/badge/PHP-7.4+-green)
![Tests](https://img.shields.io/badge/tests-38%2F38%20%E2%9C%85-green)

**Version:** 1.8.1 (December 23, 2025)

## Status

✅ **PRODUCTION READY** | ✅ **QA COMPLETE** | ✅ **SECURITY VERIFIED** | ✅ **100% TESTS PASSING** | ✅ **WORDPRESS.ORG COMPLIANT**

## Overview

LounGenie Portal is a commercial enterprise SaaS portal plugin built for WordPress. It provides a complete partner management system with two distinct user roles: Support and Partner.

**Key Features:**
- ✅ Secure `/portal` route with authentication
- ✅ Role-based access control (Support & Partner)
- ✅ Complete database schema for companies, units, and service requests
- ✅ REST API for all operations
- ✅ **HubSpot CRM Integration** - Auto-sync companies, units, and tickets
- ✅ **Microsoft Graph/Outlook Integration** - Email notifications and replies
- ✅ Modern, isolated design system (no theme dependencies)
- ✅ Responsive, enterprise-grade UI
- ✅ Semantic HTML with vanilla JavaScript
- ✅ CodeQL security verified (0 vulnerabilities)
- ✅ **Comprehensive Test Suite** - 38 tests, 100% pass rate

## WordPress as Backend Framework

This plugin uses WordPress **strictly as a backend framework** for:
- Authentication & user management
- Database abstraction (`$wpdb`)
- REST API infrastructure
- Admin capabilities

**This is NOT:**
- ❌ A theme
- ❌ A page builder
- ❌ Using shortcodes
- ❌ Using frontend frameworks (Bootstrap, React, etc.)
- ❌ Dependent on active theme

## Installation

1. Upload the `loungenie-portal` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Navigate to `/portal` route (redirects to login if not authenticated)

## User Roles

### Support Team Role (`lgp_support`)

**Capabilities:**
- View all companies and management companies
- View all LounGenie units
- Track installs, service, maintenance, updates
- View and manage all tickets
- View partner locations on map
- Full dashboard access
- Filter, search, and sort all data

### Partner Company Role (`lgp_partner`)

**Capabilities:**
- View only their company and management company
- View their LounGenie unit count
- Submit service/install/update requests
- Track request status and history
- Access stable request form

## Data Models

### Companies
- Name, address, state
- Contact information
- Management company reference (optional)

### Management Companies
- Name, address
- Contact information

### LounGenie Units
- Company and management company references
- Address, lock type, color tag
- Status (active/install/service)
- Install date and service history

### Service Requests
- Type (install/update/maintenance/repair)
- Priority level
- Status tracking
- Notes and timestamps

### Tickets
- Service request reference
- Status management
- Thread history
- Email integration reference

## REST API Endpoints

Base URL: `/wp-json/lgp/v1/`

### Companies
- `GET /companies` - List all companies (Support only)
- `GET /companies/{id}` - Get single company
- `POST /companies` - Create company (Support only)
- `PUT /companies/{id}` - Update company (Support only)

### Units
- `GET /units` - List units (filtered by role)
- `GET /units/{id}` - Get single unit
- `POST /units` - Create unit (Support only)
- `PUT /units/{id}` - Update unit (Support only)

### Tickets
- `GET /tickets` - List tickets (filtered by role)
- `GET /tickets/{id}` - Get single ticket
- `POST /tickets` - Create ticket (Partners)
- `PUT /tickets/{id}` - Update ticket (Support only)
- `POST /tickets/{id}/reply` - Add reply to thread

## Tests

The **LounGenie Portal** plugin includes a full PHPUnit test suite with **100% pass rate**:

- ✅ **38 Passing Tests** - Core functionality coverage
- ✅ **44 Assertions** - Comprehensive validation
- ✅ **Plugin Core Tests** - Class loading, constant definitions
- ✅ **Color System Tests** - Color mapping, edge cases
- ✅ **File Validator Tests** - Upload security, limits
- ✅ **Fast Execution** - Full suite runs in <100ms

### Running Tests

```bash
# Install dependencies
composer install

# Run all tests
composer run test

# Run specific test file
composer run test -- tests/CorePluginTest.php

# Verbose output
composer run test -- -v
```

### Test Documentation

- [TEST_COVERAGE_REPORT.md](TEST_COVERAGE_REPORT.md) - Detailed coverage analysis
- [TEST_DOCUMENTATION_INDEX.md](TEST_DOCUMENTATION_INDEX.md) - Complete test guide
- [TEST_SUITE_IMPROVEMENTS.md](TEST_SUITE_IMPROVEMENTS.md) - What was added

CI workflow runs automatically on the `main` branch and pull requests, generating a coverage artifact.

![CI](https://github.com/faith233525/Pool-Safe-Portal/actions/workflows/loungenie-portal-ci.yml/badge.svg)

## Design System

**Color Palette:**
```css
--primary: #3AA6B9
--secondary: #25D0EE
--dark: #04102F
--neutral: #454F5E
--background: #E9F8F9
--white: #FFFFFF
--soft: #CAE6E8
--accent: #C8A75A
--text: #222222
```

**Layout:**
- Fixed header (64px)
- Left sidebar navigation (260px)
- Main content area with padding
- Responsive breakpoints at 1024px and 768px

**Components:**
- Cards with soft shadows
- Sortable/filterable/searchable tables
- Form elements with focus states
- Badges for status indicators
- Pagination controls
- Alert and notification system

## File Structure

```
loungenie-portal/
├── loungenie-portal.php          # Main plugin file
├── uninstall.php                 # Cleanup on uninstall
├── README.md                     # Documentation
├── assets/
│   ├── css/
│   │   └── portal.css           # Complete design system
│   └── js/
│       └── portal.js            # Portal functionality
├── templates/
│   ├── portal-shell.php         # Main layout shell
│   ├── dashboard-support.php    # Support dashboard
│   └── dashboard-partner.php    # Partner dashboard
├── api/
│   ├── companies.php            # Companies REST API
│   ├── units.php                # Units REST API
│   └── tickets.php              # Tickets REST API
├── roles/
│   ├── support.php              # Support role definition
│   └── partner.php              # Partner role definition
└── includes/
    ├── class-lgp-database.php   # Database schema
    ├── class-lgp-router.php     # Route handling
    ├── class-lgp-auth.php       # Authentication
    ├── class-lgp-assets.php     # Asset management
    ├── class-lgp-hubspot.php    # HubSpot CRM integration
    └── class-lgp-outlook.php    # Microsoft Graph email integration
```

## Enterprise Integrations

### HubSpot CRM Integration

Automatically syncs portal data with HubSpot CRM:

**Features:**
- Auto-create HubSpot companies when companies are added
- Sync service requests as HubSpot tickets
- Associate tickets with companies
- Map portal status to HubSpot pipeline stages
- Retry failed syncs automatically
- Error logging visible in admin

**Setup:**
1. Go to HubSpot → Settings → Integrations → Private Apps
2. Create new private app with scopes: `crm.objects.companies.write`, `tickets`
3. Copy access token
4. In WordPress: Settings → HubSpot Integration
5. Paste API key and save

**Admin Page:** WordPress Admin → Settings → HubSpot Integration

### Microsoft Graph / Outlook Integration

Send emails and notifications via Microsoft Graph API:

**Features:**
- Send ticket replies via Outlook/Microsoft Graph
- Automated email notifications to partners
- OAuth 2.0 authentication with Azure AD
- Secure token management with auto-refresh
- Email thread history logged in portal

**Setup:**
1. Go to Azure Portal → App Registrations
2. Create new app with redirect URI: `https://yoursite.com/wp-admin/options-general.php?page=lgp-outlook-settings&oauth_callback=1`
3. Add API permissions: `Mail.Send`, `Mail.ReadWrite`, `offline_access`
4. Create client secret
5. In WordPress: Settings → Outlook Integration
6. Enter Client ID and Client Secret
7. Click "Authenticate with Microsoft"

**Admin Page:** WordPress Admin → Settings → Outlook Integration

## Usage

### Creating Support Team Users

1. Go to WordPress Admin → Users → Add New
2. Create user with username and password
3. Assign role: **LounGenie Support Team**
4. User can now access `/portal` with full privileges

### Creating Partner Company Users

1. Create user in WordPress
2. Assign role: **LounGenie Partner Company**
3. Set user meta `lgp_company_id` to link to a company
4. User can access `/portal` with limited privileges

### Assigning Company to Partner Company User

```php
update_user_meta( $user_id, 'lgp_company_id', $company_id );
```

## Unit & Color Tracking

**Important:** Units are tracked as **company-level aggregates**, not individually.

- Total unit count per company (integer)
- Color distribution stored as JSON: `{"yellow": 10, "orange": 5}`
- No individual unit IDs exposed to Partners
- Icons used throughout (no emojis)

**Documentation:**
- 📘 [Complete Guide](UNIT_COLOR_GUIDANCE.md) - Technical reference
- 📋 [Implementation Summary](UNIT_COLOR_IMPLEMENTATION_SUMMARY.md) - What's been done
- 🔖 [Quick Reference](UNIT_COLOR_QUICKREF.md) - Fast lookup

**Usage:**
```php
// Get color aggregates
$colors = LGP_Company_Colors::get_company_colors( $company_id );
$total = LGP_Company_Colors::get_company_unit_count( $company_id );

// Display in UI
lgp_render_company_colors( $company_id );
```

## Technical Standards

- ✅ Semantic HTML5
- ✅ CSS Grid and Flexbox
- ✅ Vanilla JavaScript (no jQuery)
- ✅ No inline styles
- ✅ No global CSS pollution
- ✅ Proper permission checks on all endpoints
- ✅ Nonce validation for forms
- ✅ REST API architecture
- ✅ Responsive design (mobile-first)

## Security

- Password-protected routes
- Role-based capability checks
- Permission callbacks on REST endpoints
- Nonce verification
- Data sanitization and escaping

## Documentation & Archive

Additional project documents (audits, delivery reports, and historical summaries) have been moved to a dedicated archive to keep the repository root clean:

- Docs archive: `../docs/archive/`
- Demo pages: `../docs/demos/`

For a curated list of what was archived and why, see `../docs/ARCHIVE_RECOMMENDATIONS.md`.
- SQL injection protection via `$wpdb->prepare()`

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Android)

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.0+

## License

GPL-2.0-or-later

## Support

For support inquiries, contact the LounGenie team.
