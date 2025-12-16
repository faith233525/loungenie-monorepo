# Design Previews

This directory contains **standalone HTML design previews ONLY** for the LounGenie + PoolSafe Partner & Support Portal.

## ⚠️ Important

These files are **NOT production code** and are:

- ✅ Used for design exploration and stakeholder previews
- ✅ Excluded from CI, tests, CodeQL, and builds
- ✅ Standalone HTML files with inline CSS/JS
- ❌ NOT deployed to production
- ❌ NOT tested or linted

## Files

### `partner-support-portal.html`
**Fully Interactive Portal Preview**

A comprehensive, functional preview demonstrating:
- Navigation between Dashboard, Units, Tickets, and Analytics views
- Interactive filtering and sorting for Units and Tickets tables
- CSV export functionality
- Modal dialogs for viewing details
- Keyboard shortcuts (Ctrl+K to clear filters, Escape to close modals)
- LocalStorage persistence for active view
- Vanilla JavaScript (no frameworks)

Features:
- Dashboard with live stats (Active Tickets, Service Requests, Compliance, Active Units)
- Top 5 Support Analytics (Most Active Properties, Common Service Requests)
- Units Management with multi-filter table (Status, Compliance, Property search)
- Support Tickets with multi-filter table (Status, Priority, Search)
- Responsive design (1024px, 768px breakpoints)
- WCAG 2.1 AA accessibility compliance

### `portal-color-preview.html`
**Color System Reference & Static Preview**

A comprehensive design system reference showing:
- Complete color palette with hex codes and usage guidelines
- All UI components (navigation, cards, tables, forms, alerts, buttons)
- Brand colors: Teal #1BB3B8, Gold #F2B705, Dark Blue #0B2D4F
- Status colors: Success #16A34A, Warning #F59E0B, Error #DC2626, Info #1BB3B8
- Text colors: Primary #1F2933, Secondary #6B7280, Disabled #9CA3AF
- Accessibility standards documentation

## Portal Purpose

**Partner & Support Portal** - Internal portal for:
- Managing units, tickets, and service requests
- Viewing operational analytics (Top 5 metrics only)
- Tracking compliance and deployments
- **NO financial or revenue UI**

## Design System Rules

### Brand Colors (15-25% usage)
- **Teal** `#1BB3B8` - Primary actions, links, active states
- **Gold** `#F2B705` - Priority highlights (NOT revenue)
- **Dark Blue** `#0B2D4F` - Navigation, headers, trust/authority

### Neutrals (70-80% usage)
- **Background** `#F7F9FB` - Page background
- **Cards** `#FFFFFF` - Card/panel backgrounds

### Status Colors (5% usage)
- **Success** `#16A34A`
- **Warning** `#F59E0B`
- **Error** `#DC2626`
- **Info** `#1BB3B8`

### Text Colors (Never pure black)
- **Primary** `#1F2933` - Main content
- **Secondary** `#6B7280` - Supporting text
- **Disabled** `#9CA3AF` - Disabled states

## Technical Constraints

- Single standalone HTML files
- Inline CSS with CSS custom properties
- Vanilla JavaScript (no frameworks)
- No WordPress dependencies
- No build process required
- Open directly in any modern browser

## UX / Accessibility

- WCAG 2.1 AA contrast ratios (minimum 4.5:1)
- No pure black text (#000000)
- Keyboard focus states and shortcuts
- Responsive breakpoints (1024px, 768px)
- Hover and active states for all interactive elements
- Focus indicators for keyboard navigation

## Usage

Simply open the HTML files in any modern web browser. No server, build process, or dependencies required.

```bash
# Open in default browser (macOS)
open partner-support-portal.html

# Open in default browser (Linux)
xdg-open partner-support-portal.html

# Open in default browser (Windows)
start partner-support-portal.html
```

## Development Notes

These files are intentionally excluded from:
- CI/CD pipelines
- CodeQL security scanning
- Automated testing
- Build processes
- Production deployments

They exist solely for design review, stakeholder demonstrations, and as a reference for implementing the actual production portal.
