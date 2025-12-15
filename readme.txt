=== PoolSafe Portal ===
Contributors: PoolSafe Inc
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.7.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

PoolSafe Partner Portal - A comprehensive WordPress plugin for managing pool maintenance partners, tickets, and communications.

Features:
- Partner Management & Dashboard
- Ticket System (Create, Track, Resolve)
- Azure AD / Outlook Integration
- Real-time Notifications
- CSV Import/Export
- Role-Based Access Control
- Knowledge Base & Training Videos
- Calendar Integration
- Service Records & Activity Logs
- GDPR Compliance Tools

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress Admin
3. Go to PoolSafe Portal Settings to configure
4. Add Azure credentials for Outlook login
5. Configure API endpoints and ticketing options

== Configuration ==

See DEPLOYMENT_README.md for detailed setup instructions.

Key Settings:
- Outlook / Microsoft Graph API Credentials
- HubSpot CRM Integration
- Ticketing Options
- CSV Import/Export Settings

== Support ==

For support and documentation, visit the admin settings page.

== Documentation ==

For a map of all project docs, see `DOCS_OVERVIEW.md` in the plugin root. Quick highlights and deployment steps are in `PRODUCTION_IMPROVEMENTS.md` and `QUICK_START.md`.

== Changelog ==

= 2.5.3 =
- Added comprehensive AI agent documentation (.github/copilot-instructions.md)
- 570+ lines of development guidance including architecture, common tasks, troubleshooting
- Complete database schema and integration documentation (Azure AD, HubSpot)
- Plugin lifecycle hooks and development workflow documentation
- Fixed template literal syntax in partner profile rendering
- Removed optional chaining operators for broader browser compatibility
- Updated CSP directives to allow Leaflet.js and Google Fonts
- Improved translation loading timing (plugins_loaded hook, priority 1)
- Release date: 2025-12-08

= 2.5.2 =
- Fixed JavaScript syntax errors in unified portal
- Updated Azure AD authentication to use Settings page
- Improved cache-busting headers
- Enhanced error handling and logging
