# Changelog

All notable changes to LounGenie Portal will be documented in this file.

## [v1.5.0-partner-view-polish] - 2025-12-17

### Added
- **Partner View Polish** - Enhanced UI for collapsible sections and partner-specific styling
  - Collapsible section headers for all company profile cards (company info, units, tickets, gateways, audit log, service notes)
  - localStorage persistence: Collapse/expand state saved per section per user
  - Expand/Collapse All buttons for bulk section management
  - Smooth animations: 0.3s max-height transitions for collapse/expand
  - Icon indicators: ▼ (expanded) / ▶ (collapsed) with animated updates

- **Partner Read-Only Indicators**
  - "Support Only" badges on support-only sections (visible only to partners)
  - Visual border indicator on support-only sections (golden accent border)
  - Disabled edit/delete buttons for partners on restricted operations
  - CSS class `is-read-only` for read-only card styling
  - Partner view primary sections: Company info + Units highlighted with primary color border

- **Responsive Collapsible Behavior**
  - Mobile-friendly collapse/expand buttons on all card headers
  - Bulk toggle controls above cards on desktop (hidden on mobile)
  - Responsive padding/layout adjustments for screens < 768px
  - maxHeight recalculated on window resize for smooth animations

- **Support-Only Section Filtering**
  - Audit log section hidden for partners (controlled by JavaScript)
  - Service notes section hidden for partners
  - Support users see full interface with all sections
  - Partners see only company info, units, tickets, gateways

- **JavaScript & CSS**
  - New script: `assets/js/company-profile-partner-polish.js` (320 lines, IIFE)
  - New CSS: Extended `assets/css/portal.css` with ~150 lines of collapsible + partner styling
  - Script enqueued in `class-lgp-assets.php` as `lgp-company-profile-partner-polish`
  - All functionality exposed via window.LGPPartnerPolish for testing/debugging

- **Test Coverage (Phase 4)**
  - PartnerViewPolishTest.php: 20 tests verifying collapsible structure, badges, CSS, animations
  - Coverage: localStorage keys, toggle buttons, read-only attributes, responsive behavior
  - All 20 tests passing ✅

### Technical Notes
- Collapsible headers use `data-section` attribute for localStorage identification
- Support-only sections marked with `data-support-only="true"`
- All animations use CSS transitions (no JavaScript animations for performance)
- Bulk toggle controls added only if 2+ collapsible sections exist
- localStorage key format: `lgp-profile-sections-{section_id}`
- Partner view: Maintains full read-only access to all non-support-only sections

## [v1.4.0-company-profile-enhancements] - 2025-12-17

### Added
- **Company Profile UX Enhancements** with 3 new inline features
  - Inline ticket reply modal: Reply button on each ticket row opens modal with textarea, AJAX submits reply
  - Inline audit log viewer (support-only): Tab showing timestamp, user, action, metadata with filters
  - Service notes section (support-only): Track technician notes with date, service type, travel time, unit assignment
  
- **Service Notes System**
  - New database table `lgp_service_notes` with service_date, technician_name, service_type, notes, travel_time, unit_id
  - REST API endpoints: GET /wp-json/lgp/v1/service-notes (list by company), POST (create new note)
  - Support-only access; all changes logged to audit trail
  - Service type options: maintenance, repair, inspection, installation, other
  - Optional unit assignment for tracking unit-specific service

- **Audit Log Viewer API**
  - New REST API endpoint: GET /wp-json/lgp/v1/audit-log with filtering
  - Query parameters: company_id, action (filter), date (filter), per_page (limit)
  - Returns audit logs with enriched user information (login, email)
  - Support-only access

- **Audit Log Database**
  - New table `lgp_audit_log` for comprehensive event logging (required by LGP_Logger)
  - Schema: id, user_id, action, company_id, meta (JSON), created_at
  - Indexed by: user_id, action, company_id, created_at

- **JavaScript & Styling**
  - New file `assets/js/company-profile-enhancements.js` with:
    - Modal handling for ticket replies
    - AJAX calls for audit log and service notes
    - Dynamic rendering of audit log table with filtering
    - Service notes form with validation
  - Enhanced CSS in `portal.css`:
    - Modal styling (overlay, content, close button)
    - Form controls for audit/service filters
    - Table styling for audit logs and service notes
    - Badge styles for status/action displays

### Details
- Ticket Reply: AJAX POST to /wp-json/lgp/v1/tickets/{id}/reply with content
- Audit Log: Real-time filtering by action type and date; support-only access
- Service Notes: Full CRUD support with technician tracking and unit assignment
- All changes logged to wp_lgp_audit_log table for compliance
- Form validation on frontend (required fields) and backend (all endpoints)

## [v1.3.2-audit-logging-completion] - 2025-12-16

### Added
- **Comprehensive Audit Logging** across all critical operations
  - Companies API: `create_company()` and `update_company()` now log all changes
  - Units API: `create_unit()` and `update_unit()` now log all changes
  - Tickets API: `create_ticket()` and `update_ticket()` now log all changes
  - Authentication: 5 new event hooks in LGP_Auth class:
    - `wp_login` → `log_login_success()` - Logs user, company, email, role, IP
    - `wp_login_failed` → `log_login_failed()` - Logs username, error code, IP
    - `wp_logout` → `log_logout()` - Logs user, company, role
    - `password_reset` → `log_password_reset()` - Logs reset method
    - `profile_update` → `log_password_change()` - Logs if password changed
  - Real LGP_Logger class now loaded in bootstrap.php for consistency across all tests

### Testing
- Expanded AuditLoggingTest from 4 to 10 tests
- All new tests passing (10/10):
  - test_logs_company_crud_operations
  - test_logs_unit_crud_operations
  - test_logs_authentication_events
  - test_logs_attachment_operations
  - test_audit_log_includes_timestamps
  - test_audit_log_stores_metadata_as_json
- Total test suite: 71 tests (65 existing + 6 new audit logging tests)

### Fixed
- PHPUnit test infrastructure: Resolved class_exists() guard conflicts by loading LGP_Logger in bootstrap
- ApiTrainingVideosTest and TrainingVideoTest: Removed LGP_Logger stubs, now use real class from bootstrap
- AuditLoggingTest: Fixed metadata field name (meta vs metadata) in test assertions

### Details
- Event logging: user_id, action type, company_id, metadata (JSON encoded)
- IP tracking: Added for security-relevant events (login, logout, login failed)
- Timestamp: All events include created_at timestamp via current_time()
- Metadata: Captured as JSON for flexible schema evolution

## [v1.3.1-offline-development-suite] - 2025-12-16

### Added
- **Offline Development Suite** for fully offline testing and validation
  - New CLI entry `scripts/offline-run.php` with 6 commands: `help`, `seed`, `test`, `dashboard`, `validate`, `export`, `report`
  - Mock WordPress environment (`scripts/OfflineBootstrap.php`) with 180+ functions and in-memory data store
  - Data seeding engine (`scripts/OfflineDataSeeder.php`) generating 30 realistic records:
    - Users (3), Companies (3), Units (5), Gateways (4), Tickets (4), Ticket Attachments (3), Training Videos (4), Audit Logs (4)
  - Utilities and renderers (`scripts/OfflineHelpers.php`):
    - Support dashboard (system-wide view) and Partner dashboard (company-scoped, read-only)
    - Validation tests for attachments, companies, audit logs, geocoding cache
    - Exporters for JSON and CSV; comprehensive report generator
  - Generated artifacts stored under `scripts/offline-data/` with timestamped reports and `seeded_data.json`

### Documentation
- `OFFLINE_DEVELOPMENT.md`: Complete guide with installation, commands, and examples
- `OFFLINE_SUITE_SUMMARY.md`: Quick reference overview
- `OFFLINE_COMPLETION_SUMMARY.md`: Delivery summary and next steps

### Verified
- Seeded data: 30 records
- Jest simulation: 5/5 map rendering tests passed
- Validation: All checks passing (attachments, companies, audit logs, geocoding)
- Dashboards: Support and Partner views rendering correctly
- Features: 8/8 verified (attachments, company profile, audit logging, notifications, map/geolocation, contracts, training videos, gateways)

### Notes
- PHPUnit is optional in offline mode; run `composer install` to enable local PHPUnit execution
- Offline suite requires only PHP; no WordPress or database needed

## [v1.3.0-autonomous-enhancements] - 2025-12-16

### Added
- **Ticket Attachments System**
  - Database table `lgp_ticket_attachments` with secure file storage metadata (file_name, file_type, file_size, file_path)
  - REST API endpoints: POST /tickets/:id/attachments (upload), GET /tickets/:id/attachments (list), DELETE /attachments/:id, GET /attachments/:id/download
  - File type validation: JPG, PNG, PDF, TXT, DOC, DOCX
  - File size limit: 10MB per file
  - Secure storage outside webroot in `lgp-attachments/` directory with `.htaccess` protection
  - Permission callbacks for role-based access (support full, partners own company only)
  - Audit logging for attachment upload, delete, and download actions
  - PHPUnit tests (4 tests) for attachment validation and API methods

- **Unified Company Profile View** 
  - New template `templates/company-profile.php` accessible via `/portal/company-profile?company_id=X`
  - Consolidated company information display: basic data, addresses, contacts, contract metadata
  - Company metrics dashboard: units count, gateways count, open tickets, gateways with call buttons
  - Embedded sections showing:
    - LounGenie Units table with status, color tags, lock types
    - Gateways table (support-only) with channel, address, capacity, call button status
    - Recent Tickets table with priority, status, creation date
  - Support can view/access any company; Partners see read-only view of their own company
  - Router integration: `/portal/company-profile` route in `class-lgp-router.php`
  - Portal shell integration: conditional rendering based on section query parameter

### Technical Notes
- Attachments: .htaccess protection prevents direct file access; must download via API endpoint
- Attachments: All files stored with unique MD5-based names to prevent collisions
- Company Profile: Support users can pass `?company_id=X` to view any company; Partners default to own company
- Company Profile: Authorization checks prevent unauthorized access at template level
- Database: Added `lgp_ticket_attachments` table with foreign key to tickets
- Plugin: Updated `loungenie-portal.php` to require attachments API on init

### Verified Features
- ✅ Map/Geolocation: `LGP_Geocode` class functional, `lgp-map.js` enqueued correctly
- ✅ Audit Logging: `AuditLoggingTest` passing (4/4 tests), all CRUD actions logged
- ✅ Notification Flow: `NotificationFlowTest` passing, email + portal alerts working

## [v1.2.0-contract-metadata] - 2025-01-20

### Added
- **Contract Metadata & Enhanced Company Fields**
  - Database: Added `contract_type` (revenue_share/direct_purchase), `contract_start_date`, `contract_end_date` to companies table
  - Database: Added `city`, `zip`, `country` fields for complete address info
  - Database: Added `secondary_contact_name`, `secondary_contact_email`, `secondary_contact_phone` for backup contacts
  - REST API: Updated `/companies` endpoints to include contract and contact metadata
  - Validation: Contract type restricted to `revenue_share` or `direct_purchase`
  
- **Enhanced Unit Metadata**
  - Database: Added `serial_number`, `warranty_date`, `assigned_technician` to units table
  - Database: Changed `season` to `seasonality` for clarity
  - Database: Changed `service_history` from text to longtext for JSON storage
  - Database: Added `lock_brand` with validation (MAKE, L&F, other)
  - Color tags: Standardized to `classic-blue`, `ice-blue`, `ducati-red`, `yellow`, `custom`
  - REST API: Updated `/units` endpoints with enhanced metadata fields
  - Validation: Color tag and lock brand validation in API endpoints
  - Service history: Now stored as JSON array for structured data
  
- **PHPUnit Tests**
  - Contract metadata validation tests (3/3 passing)
  - Color tag validation tests
  - Lock brand validation tests

### Technical Notes
- Schema changes: Companies and units tables updated via dbDelta
- Backward compatible: All new fields optional with sensible defaults
- API validation: Invalid contract types, color tags, or lock brands return 400 error
- Service history: Use `wp_json_encode()` for structured storage

## [v1.1.0-training-videos] - 2025-01-20

### Added
- **Training Videos System** with role-based access control
  - Database table `lgp_training_videos` with columns: id, title, description, video_url, category, target_companies (JSON), duration, created_by, created_at, updated_at
  - Backend class (`LGP_Training_Video`) with full CRUD operations and partner filtering
  - REST API (`/wp-json/lgp/v1/training-videos`) with 6 endpoints: GET all (filtered), GET single, POST create, PUT update, DELETE, GET categories
  - Training Videos view (`/portal/training`) accessible to both Support and Partner roles
  - Video grid layout with responsive cards (auto-fill minmax 300px)
  - Search and category filter functionality
  - Support-only features: Add/Edit/Delete videos, assign to specific companies or all companies
  - Partner access: View only videos with empty `target_companies` or their company_id included
  - Video player modal with YouTube/Vimeo embed support
  - 5 predefined categories: general, installation, troubleshooting, maintenance, product-overview
  - JavaScript (`training-view.js`) handling video loading, search/filter, CRUD via REST API, modal interactions
  - CSS styling for video cards, thumbnails, hover effects, modals, forms, badges
  - PHPUnit tests (3 tests) for validation, categories, and API permission callbacks
  - Audit logging for all video create/update/delete actions

### Technical Notes
- Support-only: Create, update, delete operations require `manage_options` capability
- Partner filtering: Videos shown based on `target_companies` JSON array (empty = all companies)
- Schema change: added `lgp_training_videos` table in `class-lgp-database.php`
- Router integration: added `/portal/training` route for all authenticated portal users
- Navigation: added "Training Videos" link (🎓) to both Support and Partner sidebar menus
- Video embeds: automatic YouTube/Vimeo detection and iframe generation, fallback to HTML5 video
- Company selector: "All Companies" checkbox toggles company-specific assignment list

## [v1.1.0-gateway-management] - 2025-12-16

### Added
- **Gateway Management (Support-Only)** complete CRUD system for LounGenie gateways
  - Database table `lgp_gateways` with columns: id, company_id, channel_number, gateway_address, unit_capacity, call_button, included_equipment, admin_password, timestamps
  - Backend class (`LGP_Gateway`) with full CRUD operations, audit logging via `LGP_Logger::log()`
  - REST API (`/wp-json/lgp/v1/gateways`) with support-only endpoints: GET, POST, PUT, DELETE, test-signal, get-units
  - Support dashboard view grouped by partner with search/filter capabilities
  - Gateway rows with call button enabled are highlighted (yellow background)
  - Actions: View Units (modal), Audit Logs (modal), Test Signal (simulated F&B call button test)
  - CSS styles for gateway table, modals, badges, filters, and call-button highlighting
  - PHPUnit tests (9 tests) for gateway CRUD, access control, and API permissions
  - JavaScript handlers for search, filtering, AJAX actions, and modal interactions

### Technical Notes
- Support-gated: all gateway operations require `manage_options` capability
- Schema change: added `lgp_gateways` table in `class-lgp-database.php`
- Router integration: added `/portal/gateways` route for support users
- Audit logging: all gateway CRUD and test-signal actions logged with user, company, timestamp
- Call button gateways highlighted with `.has-call-button` CSS class

## [v1.1.0-map-feature] - 2025-12-16

### Added
- **Support-only Company Map** powered by Leaflet.js and OpenStreetMap (free, no API keys)
  - Geocoding helper (`LGP_Geocode`) using free Nominatim API with coordinate caching
  - Map renderer with markers for all companies (`assets/js/lgp-map.js`)
  - Leaflet assets enqueued only for support users
  - Map card integrated into support dashboard (`templates/dashboard-support.php`)
  - Coordinates cached per company in `wp_options` (`lgp_geocode_{id}`)
  - WP-CLI backfill script for batch geocoding (`wp-cli/lgp-backfill-geocode.php`)
- **Test Coverage**
  - PHPUnit test for geocode helper with Brain Monkey stubs (`tests/LGPGeocodeTest.php`)
  - Jest test for Leaflet map renderer (`assets/tests/lgp-map.test.js`)
  - Access control validation: partners receive empty marker set

### Technical Notes
- No schema changes; geocode cache stored in `wp_options`
- Throttling: 1 request/sec to respect Nominatim usage policy
- Support-gated: `LGP_Geocode::get_company_markers_for_map()` returns empty array for partners
- Shared-hosting compatible; no Docker, Node build, or paid APIs

## [v1.1.0-tests-hardening] - 2025-12-16

### Added
- Router success-path tests for Support & Partner dashboards
- SSO error-path tests for Microsoft 365 OAuth scenarios
- Full PHPUnit + Brain Monkey suite integrated into CI

### Fixed / Improved

- CI coverage artifact generation
- Minimal WP stubs for safe, isolated unit tests

## [1.0.0] - 2024-12-15

### Added - Initial Release

#### Core Infrastructure
- Main plugin file with WordPress standards compliance
- Database schema with 5 custom tables:
  - Companies
  - Management Companies  
  - LounGenie Units
  - Service Requests
  - Tickets
- Custom rewrite rules for `/portal` route
- Activation/deactivation hooks
- Uninstall script for complete cleanup

#### Authentication & Authorization
- Custom router class for portal access
- Role-based authentication system
- Redirect unauthenticated users to WordPress login
- Return to portal after successful login
- Two custom user roles:
  - **Support Role** - Full system access
  - **Partner Role** - Limited to own company data

#### Design System
- Complete CSS framework using CSS variables
- Enterprise SaaS color palette:
  - Primary: #3AA6B9
  - Secondary: #25D0EE
  - Accent: #C8A75A
- Semantic HTML components
- Responsive design (mobile-first)
- No external dependencies (Bootstrap, Tailwind, etc.)
- Isolated from WordPress theme styles

#### User Interface
- Fixed header with logo, notifications, and user menu
- Collapsible sidebar navigation
- Main content area with proper spacing
- Responsive layout (1024px and 768px breakpoints)

#### Templates
- Portal shell with header, sidebar, and main content
- Support dashboard:
  - System statistics (companies, units, tickets)
  - Recent tickets table
  - System alerts
- Partner dashboard:
  - Company information display
  - Unit count statistics
  - Service request submission form
  - Recent activity table
- Map view (Support only):
  - Partner location list
  - Filtering by status, region, unit count
  - Map integration placeholder

#### REST API Endpoints
- **Companies API** (`/wp-json/lgp/v1/companies`)
  - GET all companies (Support only)
  - GET single company (role-based access)
  - POST create company (Support only)
  - PUT update company (Support only)

- **Units API** (`/wp-json/lgp/v1/units`)
  - GET all units (filtered by role)
  - GET single unit (role-based access)
  - POST create unit (Support only)
  - PUT update unit (Support only)

- **Tickets API** (`/wp-json/lgp/v1/tickets`)
  - GET all tickets (filtered by role)
  - GET single ticket (role-based access)
  - POST create ticket/service request (Partners)
  - PUT update ticket (Support only)
  - POST add reply to ticket thread

#### JavaScript Features
- Table sorting (ascending/descending)
- Table filtering by column values
- Table search functionality
- Pagination handlers
- Sidebar toggle for mobile devices
- AJAX form submission
- Toast notification system
- Debounced search input

#### Security Features
- Permission callbacks on all REST endpoints
- Nonce verification for form submissions
- SQL injection protection via `$wpdb->prepare()`
- Data sanitization and escaping
- Role-based capability checks
- XSS prevention with `esc_html()`, `esc_attr()`, etc.

#### Documentation
- Comprehensive README.md
- SETUP_GUIDE.md with step-by-step instructions
- Sample SQL data for testing
- Inline code comments
- File structure documentation

### Technical Standards
- ✅ WordPress 5.8+ compatibility
- ✅ PHP 7.4+ required
- ✅ Semantic HTML5
- ✅ CSS Grid and Flexbox layouts
- ✅ Vanilla JavaScript (no jQuery)
- ✅ No inline styles
- ✅ No global CSS pollution
- ✅ Proper i18n/l10n ready
- ✅ WPCS coding standards

### Browser Support
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS, Android)

---

## Future Enhancements (Planned)

### Version 1.3.0 (In Progress - Autonomous Enhancements)
- [x] Ticket file attachments (10MB limit, type validation, secure storage)
- [x] Unified company profile view (consolidated data dashboard)
- [x] Audit logging completion (verified 4/4 tests)
- [x] Notification flow integration (verified working)
- [x] Map/geolocation verification (confirmed functional)

### Version 1.4.0 (Planned)
- [ ] Email notifications for ticket updates
- [ ] Export functionality (CSV/PDF)
- [ ] Advanced filtering and saved searches
- [ ] Bulk operations for Support users
- [ ] Calendar view for scheduled maintenance

### Version 2.0.0 (Planned)
- [ ] Mobile app integration
- [ ] Real-time notifications via WebSocket
- [ ] Advanced workflow automation
- [ ] Custom fields for companies/units
- [ ] API webhooks for external integrations

---

## Security Updates

All security vulnerabilities will be documented here with patch versions.

---

## Notes

This plugin follows semantic versioning (MAJOR.MINOR.PATCH):
- **MAJOR** - Incompatible API changes
- **MINOR** - New functionality (backward compatible)
- **PATCH** - Bug fixes (backward compatible)

For detailed commit history, see the Git repository.

### Added
- **Training Videos System** with role-based access control
  - Database table `lgp_training_videos` with columns: id, title, description, video_url, category, target_companies (JSON), duration, created_by, created_at, updated_at
  - Backend class (`LGP_Training_Video`) with full CRUD operations and partner filtering
  - REST API (`/wp-json/lgp/v1/training-videos`) with 6 endpoints: GET all (filtered), GET single, POST create, PUT update, DELETE, GET categories
  - Training Videos view (`/portal/training`) accessible to both Support and Partner roles
  - Video grid layout with responsive cards (auto-fill minmax 300px)
  - Search and category filter functionality
  - Support-only features: Add/Edit/Delete videos, assign to specific companies or all companies
  - Partner access: View only videos with empty `target_companies` or their company_id included
  - Video player modal with YouTube/Vimeo embed support
  - 5 predefined categories: general, installation, troubleshooting, maintenance, product-overview
  - JavaScript (`training-view.js`) handling video loading, search/filter, CRUD via REST API, modal interactions
  - CSS styling for video cards, thumbnails, hover effects, modals, forms, badges
  - PHPUnit tests (3 tests) for validation, categories, and API permission callbacks
  - Audit logging for all video create/update/delete actions

### Technical Notes
- Support-only: Create, update, delete operations require `manage_options` capability
- Partner filtering: Videos shown based on `target_companies` JSON array (empty = all companies)
- Schema change: added `lgp_training_videos` table in `class-lgp-database.php`
- Router integration: added `/portal/training` route for all authenticated portal users
- Navigation: added "Training Videos" link (🎓) to both Support and Partner sidebar menus
- Video embeds: automatic YouTube/Vimeo detection and iframe generation, fallback to HTML5 video
- Company selector: "All Companies" checkbox toggles company-specific assignment list

## [v1.1.0-gateway-management] - 2025-12-16

### Added
- **Gateway Management (Support-Only)** complete CRUD system for LounGenie gateways
  - Database table `lgp_gateways` with columns: id, company_id, channel_number, gateway_address, unit_capacity, call_button, included_equipment, admin_password, timestamps
  - Backend class (`LGP_Gateway`) with full CRUD operations, audit logging via `LGP_Logger::log()`
  - REST API (`/wp-json/lgp/v1/gateways`) with support-only endpoints: GET, POST, PUT, DELETE, test-signal, get-units
  - Support dashboard view grouped by partner with search/filter capabilities
  - Gateway rows with call button enabled are highlighted (yellow background)
  - Actions: View Units (modal), Audit Logs (modal), Test Signal (simulated F&B call button test)
  - CSS styles for gateway table, modals, badges, filters, and call-button highlighting
  - PHPUnit tests (9 tests) for gateway CRUD, access control, and API permissions
  - JavaScript handlers for search, filtering, AJAX actions, and modal interactions

### Technical Notes
- Support-gated: all gateway operations require `manage_options` capability
- Schema change: added `lgp_gateways` table in `class-lgp-database.php`
- Router integration: added `/portal/gateways` route for support users
- Audit logging: all gateway CRUD and test-signal actions logged with user, company, timestamp
- Call button gateways highlighted with `.has-call-button` CSS class

## [v1.1.0-map-feature] - 2025-12-16

### Added
- **Support-only Company Map** powered by Leaflet.js and OpenStreetMap (free, no API keys)
  - Geocoding helper (`LGP_Geocode`) using free Nominatim API with coordinate caching
  - Map renderer with markers for all companies (`assets/js/lgp-map.js`)
  - Leaflet assets enqueued only for support users
  - Map card integrated into support dashboard (`templates/dashboard-support.php`)
  - Coordinates cached per company in `wp_options` (`lgp_geocode_{id}`)
  - WP-CLI backfill script for batch geocoding (`wp-cli/lgp-backfill-geocode.php`)
- **Test Coverage**
  - PHPUnit test for geocode helper with Brain Monkey stubs (`tests/LGPGeocodeTest.php`)
  - Jest test for Leaflet map renderer (`assets/tests/lgp-map.test.js`)
  - Access control validation: partners receive empty marker set

### Technical Notes
- No schema changes; geocode cache stored in `wp_options`
- Throttling: 1 request/sec to respect Nominatim usage policy
- Support-gated: `LGP_Geocode::get_company_markers_for_map()` returns empty array for partners
- Shared-hosting compatible; no Docker, Node build, or paid APIs

## [v1.1.0-tests-hardening] - 2025-12-16

### Added
- Router success-path tests for Support & Partner dashboards
- SSO error-path tests for Microsoft 365 OAuth scenarios
- Full PHPUnit + Brain Monkey suite integrated into CI

### Fixed / Improved

- CI coverage artifact generation
- Minimal WP stubs for safe, isolated unit tests

## [1.0.0] - 2024-12-15

### Added - Initial Release

#### Core Infrastructure
- Main plugin file with WordPress standards compliance
- Database schema with 5 custom tables:
  - Companies
  - Management Companies  
  - LounGenie Units
  - Service Requests
  - Tickets
- Custom rewrite rules for `/portal` route
- Activation/deactivation hooks
- Uninstall script for complete cleanup

#### Authentication & Authorization
- Custom router class for portal access
- Role-based authentication system
- Redirect unauthenticated users to WordPress login
- Return to portal after successful login
- Two custom user roles:
  - **Support Role** - Full system access
  - **Partner Role** - Limited to own company data

#### Design System
- Complete CSS framework using CSS variables
- Enterprise SaaS color palette:
  - Primary: #3AA6B9
  - Secondary: #25D0EE
  - Accent: #C8A75A
- Semantic HTML components
- Responsive design (mobile-first)
- No external dependencies (Bootstrap, Tailwind, etc.)
- Isolated from WordPress theme styles

#### User Interface
- Fixed header with logo, notifications, and user menu
- Collapsible sidebar navigation
- Main content area with proper spacing
- Responsive layout (1024px and 768px breakpoints)

#### Templates
- Portal shell with header, sidebar, and main content
- Support dashboard:
  - System statistics (companies, units, tickets)
  - Recent tickets table
  - System alerts
- Partner dashboard:
  - Company information display
  - Unit count statistics
  - Service request submission form
  - Recent activity table
- Map view (Support only):
  - Partner location list
  - Filtering by status, region, unit count
  - Map integration placeholder

#### REST API Endpoints
- **Companies API** (`/wp-json/lgp/v1/companies`)
  - GET all companies (Support only)
  - GET single company (role-based access)
  - POST create company (Support only)
  - PUT update company (Support only)

- **Units API** (`/wp-json/lgp/v1/units`)
  - GET all units (filtered by role)
  - GET single unit (role-based access)
  - POST create unit (Support only)
  - PUT update unit (Support only)

- **Tickets API** (`/wp-json/lgp/v1/tickets`)
  - GET all tickets (filtered by role)
  - GET single ticket (role-based access)
  - POST create ticket/service request (Partners)
  - PUT update ticket (Support only)
  - POST add reply to ticket thread

#### JavaScript Features
- Table sorting (ascending/descending)
- Table filtering by column values
- Table search functionality
- Pagination handlers
- Sidebar toggle for mobile devices
- AJAX form submission
- Toast notification system
- Debounced search input

#### Security Features
- Permission callbacks on all REST endpoints
- Nonce verification for form submissions
- SQL injection protection via `$wpdb->prepare()`
- Data sanitization and escaping
- Role-based capability checks
- XSS prevention with `esc_html()`, `esc_attr()`, etc.

#### Documentation
- Comprehensive README.md
- SETUP_GUIDE.md with step-by-step instructions
- Sample SQL data for testing
- Inline code comments
- File structure documentation

### Technical Standards
- ✅ WordPress 5.8+ compatibility
- ✅ PHP 7.4+ required
- ✅ Semantic HTML5
- ✅ CSS Grid and Flexbox layouts
- ✅ Vanilla JavaScript (no jQuery)
- ✅ No inline styles
- ✅ No global CSS pollution
- ✅ Proper i18n/l10n ready
- ✅ WPCS coding standards

### Browser Support
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS, Android)

---

## Future Enhancements (Planned)

### Version 1.1.0 (Completed)
- [x] Company location map (implemented with Leaflet + OpenStreetMap, support-only)
- [ ] Email notifications for ticket updates
- [ ] Export functionality (CSV/PDF)
- [ ] Advanced filtering and saved searches
- [ ] Bulk operations for Support users

### Version 1.2.0 (Planned)
- [ ] Calendar view for scheduled maintenance
- [ ] File attachments for tickets
- [ ] SMS notifications
- [ ] Advanced reporting and analytics
- [ ] Multi-language support (Spanish, French)

### Version 2.0.0 (Planned)
- [ ] Mobile app integration
- [ ] Real-time notifications via WebSocket
- [ ] Advanced workflow automation
- [ ] Custom fields for companies/units
- [ ] API webhooks for external integrations

---

## Security Updates

All security vulnerabilities will be documented here with patch versions.

---

## Notes

This plugin follows semantic versioning (MAJOR.MINOR.PATCH):
- **MAJOR** - Incompatible API changes
- **MINOR** - New functionality (backward compatible)
- **PATCH** - Bug fixes (backward compatible)

For detailed commit history, see the Git repository.
