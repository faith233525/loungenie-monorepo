=== LounGenie Portal ===
Contributors: loungenie
Tags: portal, support, tickets, sso, hubspot, csv import, multi-tenant
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade multi-tenant SaaS portal for partners and support, with Microsoft SSO, email-to-ticket (Graph), HubSpot sync, and role-based dashboards.

== Description ==
LounGenie Portal provides a secure, multi-tenant partner and support portal inside WordPress. Features include Microsoft SSO for support staff, partner login, ticketing, email-to-ticket via Microsoft Graph, HubSpot CRM sync, CSV partner import, geocoded units, and role-based dashboards (lgp_support, lgp_partner).

== Key Features ==
- Microsoft Entra ID SSO for support staff
- Partner login with scoped data isolation
- Ticketing with replies, status, priority, type, and unit linkage
- Email-to-ticket via Microsoft Graph shared mailbox
- HubSpot ticket sync
- CSV partner import (primary/secondary contacts)
- Geocoded units with map view
- Caching, rate limiting, audit logging, and hardened uploads
- Namespaced CSS under .lgp-portal to avoid bleed

== Installation ==
1. Upload `loungenie-portal-2.0.0.zip` via Plugins → Add New → Upload.
2. Activate the plugin.
3. Configure credentials in wp-config.php or site options:
	- MICROSOFT_GRAPH_TENANT
	- MICROSOFT_GRAPH_CLIENT_ID
	- MICROSOFT_GRAPH_CLIENT_SECRET
	- HUBSPOT_API_KEY
	- SMTP_HOST / SMTP_PORT / SMTP_USERNAME / SMTP_PASSWORD (TLS/SSL)
4. Ensure HTTPS is enabled and REST API accessible.

== Usage ==
- Support (lgp_support): Access /portal, manage all companies, tickets, units.
- Partner (lgp_partner): Access /portal, scoped to their company only.
- CSV import: Use the included `partner-import-template.csv` via the portal CSV Import admin page.

== Requirements ==
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.0+

== Changelog ==
= 2.0.0 =
* Security: Nonces on REST writes, sanitization/escaping, hardened file uploads.
* Performance: Transient caching, N+1 fixes, conditional asset loading, DB indexes, CSV chunking.
* Compatibility: Verified PHP 7.4–8.2; WordPress 5.8–6.4.
* Testing: Added PHPUnit suite (unit/integration/performance/compatibility) and CI workflow.
* Accessibility: Restored tickets view template with ARIA-friendly markup and role-aware filters.
* Docs: Added comprehensive docs and import CSV template.

= 1.9.0 =
* Health Check Endpoint - Real-time system monitoring
* Security Audit Log System - Complete tracking and compliance
* Environment Configuration System - Dev/staging/prod support
* GitHub Actions CI/CD Pipeline - Automated testing and deployment
* Database Migration System Enhancement - Version-tracked schema updates
* Enhanced security headers (CSP, HSTS)
* Multi-layer caching (Redis, Memcached, Transients)
* Performance improvements (3-4x faster dashboard loads)
* 192 unit tests with comprehensive coverage

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
