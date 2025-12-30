=== LounGenie Portal ===
Contributors: loungenie, faith233525
Tags: portal, support, tickets, sso, hubspot, csv import, multi-tenant, enterprise
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade multi-tenant SaaS portal for partners and support, with Microsoft SSO, email-to-ticket, HubSpot sync, and role-based dashboards.

== Description ==

LounGenie Portal is a secure, enterprise-grade multi-tenant portal for managing partner companies and internal support operations inside WordPress. Designed for shared hosting and high-traffic environments, it combines powerful features with exceptional performance and security.

**Core Features:**
- Microsoft Entra ID SSO for support staff (Azure Active Directory integration)
- Partner Multi-Tenant System with scoped data isolation by company
- Ticketing System with replies, status tracking, priority levels, and unit assignment
- Email-to-Ticket via Microsoft Graph shared mailbox (with fallback to POP3)
- Email Reply Detection automatically updates tickets when customers respond
- HubSpot CRM Sync for seamless ticket management in CRM
- CSV Partner Import for bulk company and contact onboarding
- Geocoded Units with interactive map view and coordinate management
- Knowledge Center with searchable articles and categorization
- Service Requests for maintenance and support tickets
- Access Gateways management (parking, entry codes, etc.)
- Audit Logging for compliance and security tracking
- Advanced Caching (transients, Redis-ready) for performance
- Rate Limiting to prevent brute-force attacks
- File Validation with MIME type checking and size limits
- Responsive Design optimized for desktop and mobile
- Scoped CSS under .lgp-portal (zero theme conflicts)

== Installation ==

1. **Download & Upload**
   - Download `loungenie-portal-2.0.0.zip`
   - In WordPress Admin: Plugins → Add New → Upload Plugin
   - Select the ZIP file and click "Install Now"

2. **Activate Plugin**
   - Click "Activate Plugin" or navigate to Plugins → Installed Plugins → LounGenie Portal → Activate

3. **Configure Credentials** (Choose one method per service)

   Microsoft Graph (Email-to-Ticket):
   ```php
   define( 'MICROSOFT_GRAPH_TENANT', 'your-tenant-id.onmicrosoft.com' );
   define( 'MICROSOFT_GRAPH_CLIENT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' );
   define( 'MICROSOFT_GRAPH_CLIENT_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
   ```

   Microsoft Entra ID (SSO for Support Staff):
   ```php
   define( 'MICROSOFT_ENTRA_ID_TENANT', 'your-tenant-id' );
   define( 'MICROSOFT_ENTRA_ID_CLIENT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' );
   define( 'MICROSOFT_ENTRA_ID_CLIENT_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
   ```

   HubSpot CRM:
   ```php
   define( 'HUBSPOT_API_KEY', 'pat-na1-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
   ```

   SMTP Email (Transactional):
   ```php
   define( 'SMTP_HOST', 'smtp.sendgrid.net' );
   define( 'SMTP_PORT', '587' );
   define( 'SMTP_USERNAME', 'apikey' );
   define( 'SMTP_PASSWORD', 'SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
   ```

4. **Enable Features**
   - Go to WordPress Admin → LounGenie Portal → Settings
   - Enable/disable email pipeline (new Graph-based or legacy POP3)
   - Configure notification email addresses
   - Set cache TTL (default: 5 minutes)

5. **Import Partners (Optional)**
   - Use included `partner-import-template.csv` as template
   - Fill in company names, contacts, emails, and phone numbers
   - Go to LounGenie Portal → CSV Import → Upload File
   - Review validation results and confirm import
   - System automatically creates user accounts and sends welcome emails

6. **Test Portal Access**
   - Support Staff: Visit `yoursite.com/portal` → Sign in with Azure AD
   - Partners: Visit `yoursite.com/portal` → Login with company password
   - Verify dashboard loads with correct metrics and data scoping

== Usage ==

**For Support Staff (lgp_support role):**
- Access: yoursite.com/portal
- Permissions: View all companies, tickets, units, and reports
- Actions: Create/edit tickets, reply to customer emails, manage gateways
- Dashboard: Company overview, ticket metrics, activity log

**For Partners (lgp_partner role):**
- Access: yoursite.com/portal
- Permissions: View only their own company data
- Actions: Create service requests, view tickets, upload attachments
- Dashboard: Company-specific metrics, unit management, ticket history

**Email-to-Ticket Workflow:**
1. Customer sends email to support mailbox
2. System parses email via Microsoft Graph (every 5 minutes)
3. Creates ticket with sender, subject, body, and attachments
4. Support staff notified via WordPress notification
5. Support replies in LounGenie Portal
6. System sends reply email back to customer
7. Customer reply updates ticket automatically

== Requirements ==

- WordPress 5.8 or higher (tested up to 6.4)
- PHP 7.4 or higher (tested up to PHP 8.2)
- MySQL 5.6 or higher (or MariaDB 10.0+)
- HTTPS strongly recommended (required for SSO)
- REST API must be enabled (default in WordPress)

**Optional (for features):**
- Microsoft Graph API credentials (for email-to-ticket)
- Microsoft Entra ID app registration (for SSO)
- HubSpot private app access key (for CRM sync)
- SMTP email service credentials (SendGrid, AWS SES, Gmail, etc.)

== Security ==

**Built-in Security Features:**
- SQL injection protection via $wpdb->prepare()
- XSS prevention via output escaping (esc_html(), esc_attr(), esc_url())
- CSRF protection via WordPress nonces
- Role-based access control (lgp_support, lgp_partner)
- Multi-tenant data isolation by company_id
- Rate limiting on login attempts (5 attempts / 5 minutes)
- File upload validation (MIME type, size, extension)
- Audit logging of all auth and admin actions
- CSP, HSTS, and X-Frame-Options headers
- WordPress password hashing for stored credentials
- Secure session management via WordPress auth cookies

== Changelog ==

= 2.0.0 =
* Complete production audit and verification
* 192 PHPUnit unit tests (100% passing)
* Comprehensive documentation and deployment guide
* Fixed critical docblock issue in main plugin file
* All SQL queries verified for prepared statements
* All output escaping verified (zero XSS vulnerabilities)
* CSS scoping verified (zero theme conflicts)
* Asset references optimized for shared hosting
* PHP 7.4–8.2 compatibility confirmed
* WordPress 5.8–6.4 compatibility confirmed
* Multi-tenant data isolation verified
* Email-to-ticket pipeline operational
* HubSpot sync integration working
* CSV import with full validation
* Performance optimized (less than 500ms API response)
* Caching layer functional (transients, Redis-ready)
* Shared hosting compatible (tested on typical hosts)
* Production Ready - Approved for enterprise deployment

= 1.9.1 =
* Health Check Endpoint for system monitoring
* Security Audit Log System for compliance tracking
* Environment Configuration System (dev/staging/prod support)
* GitHub Actions CI/CD Pipeline for automated testing
* Database Migration System with version tracking
* Enhanced security headers (CSP, HSTS)
* Multi-layer caching (Redis, Memcached, Transients)
* Performance improvements (3-4x faster dashboard)
* 182 unit tests with comprehensive coverage

= 1.9.0 =
* Email-to-Ticket via Microsoft Graph
* HubSpot CRM sync integration
* CSV partner bulk import
* Geocoded units with map view
* Knowledge center module
* Service request tracking
* Gateway access management
* Audit logging system

= 1.8.0 =
* Microsoft 365 SSO for support staff
* Multi-tenant architecture for partners
* Ticketing system with full CRUD
* Email handlers (Graph + POP3)
* Enterprise security features

= 1.0.0 =
* Initial plugin release
* Core portal and authentication functionality

== Support ==

**Documentation:**
- See PRODUCTION_RELEASE_v2.0.0.md for comprehensive setup guide
- Review inline code comments in PHP files
- Check included configuration examples

**Reporting Issues:**
- GitHub Issues: https://github.com/faith233525/Pool-Safe-Portal/issues
- Email: support@poolsafe.com

== License ==

This plugin is released under the GPLv2 or later.
GPLv2 or later: https://www.gnu.org/licenses/gpl-2.0.html
