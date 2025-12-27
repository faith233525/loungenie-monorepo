=== LounGenie Portal ===
Contributors: faith233525
Stable tag: 1.8.1
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise SaaS partner management portal for LounGenie.

== Description ==

LounGenie Portal is a comprehensive WordPress plugin providing:

- Role-based access control (Support & Partner roles)
- Company and unit management
- Ticket and service request workflows
- Microsoft 365 SSO (Azure AD OAuth 2.0)
- HubSpot CRM integration
- Microsoft Graph email integration
- REST API endpoints for all operations
- Audit logging and compliance tracking
- 60-30-10 color-based design system
- Fully isolated UI (zero theme dependencies)

== Installation ==

1. Upload `loungenie-portal` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Tables auto-created on activation
4. Configure integrations in WordPress settings

== Requirements ==

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.0+

== Features ==

### Core
- Secure `/portal` route with authentication
- Fixed header, sidebar navigation
- Support & Partner dashboards
- Company and unit management
- Service request submission
- Ticket tracking and threading

### Integrations
- Microsoft 365 SSO (Azure AD)
- HubSpot CRM sync
- Microsoft Graph email
- Outlook integration

### Security
- Role-based access control (RBAC)
- Prepared SQL queries
- Output escaping
- Nonce verification
- Audit logging

### REST API
- /wp-json/lgp/v1/companies
- /wp-json/lgp/v1/units
- /wp-json/lgp/v1/tickets
- /wp-json/lgp/v1/gateways
- /wp-json/lgp/v1/knowledge-center (legacy alias: /wp-json/lgp/v1/help-guides)
- /wp-json/lgp/v1/attachments
- /wp-json/lgp/v1/audit-log
- /wp-json/lgp/v1/service-notes

== Documentation ==

For setup and deployment:
- See included README.md
- SETUP_GUIDE.md for installation
- ENTERPRISE_FEATURES.md for integrations
- PRODUCTION_DEPLOYMENT.md for server setup

== Testing ==

- 182 unit tests with 639 assertions
- PHPUnit + Brain Monkey
- All critical paths covered
- Run: `composer run test`

== License ==

GPLv2 or later

== Changelog ==

= 1.8.1 =
* Final production deployment preparation
* Security hardening complete
* All 182 tests passing

= 1.8.0 =
* Microsoft 365 SSO integration
* HubSpot CRM sync
* Email handlers (Graph + POP3)
* Enterprise features

= 1.0.0 =
* Initial release
* Core portal functionality
