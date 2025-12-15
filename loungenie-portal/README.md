# LounGenie Portal

**Enterprise SaaS Partner Management Portal**

Version: 1.0.0

## Overview

LounGenie Portal is a commercial enterprise SaaS portal plugin built for WordPress. It provides a complete partner management system with two distinct user roles: Support and Partner.

**Key Features:**
- ✅ Secure `/portal` route with authentication
- ✅ Role-based access control (Support & Partner)
- ✅ Complete database schema for companies, units, and service requests
- ✅ REST API for all operations
- ✅ Modern, isolated design system (no theme dependencies)
- ✅ Responsive, enterprise-grade UI
- ✅ Semantic HTML with vanilla JavaScript

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

### Support Role (`lgp_support`)

**Capabilities:**
- View all companies and management companies
- View all LounGenie units
- Track installs, service, maintenance, updates
- View and manage all tickets
- View partner locations on map
- Full dashboard access
- Filter, search, and sort all data

### Partner Role (`lgp_partner`)

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
    └── class-lgp-assets.php     # Asset management
```

## Usage

### Creating Support Users

1. Go to WordPress Admin → Users → Add New
2. Create user with username and password
3. Assign role: **LounGenie Support**
4. User can now access `/portal` with full privileges

### Creating Partner Users

1. Create user in WordPress
2. Assign role: **LounGenie Partner**
3. Set user meta `lgp_company_id` to link to a company
4. User can access `/portal` with limited privileges

### Assigning Company to Partner

```php
update_user_meta( $user_id, 'lgp_company_id', $company_id );
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
