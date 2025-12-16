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

## Design System Rules - Modern SaaS Palette

### Core Brand Colors (NO GOLD - Clean & Professional)
- **Primary Navy** `#04102F` - Structure, trust, sidebar, headers
- **Primary Teal** `#3AA6B9` - Interaction, buttons, links, active states
- **Soft Aqua** `#25D0EE` - Hover, focus, highlights (minimal usage)

### Neutrals (70-80% usage) - Depth through elevation, not color
- **Page Background** `#F5F7FA` - Main page surface
- **Surface Light** `#E9F8F9` - Card backgrounds
- **Pure White** `#FFFFFF` - Primary surface, modals
- **Cool Gray** `#CAE6E8` - Borders, dividers

### Status Colors (5% usage - Muted, low saturation)
- **Success (Muted Teal)** `#3AA6B9` - Positive states
- **Warning (Soft Amber)** `#D97706` - Caution states (desaturated)
- **Error** `#DC2626` - Critical issues (muted red)
- **Info (Slate Blue)** `#6B7280` - Informational

### Text Colors (Never pure black)
- **Charcoal** `#222222` - Main content
- **Slate Gray** `#454F5E` - Labels, secondary text
- **Disabled** `#9CA3AF` - Disabled states

### Modern SaaS Design Strategy
✓ Navy + Teal core palette (NO GOLD)  
✓ Depth through elevation, shadows, and layering  
✓ Typography weight for visual hierarchy  
✓ Subtle motion and transitions  
✓ Clean, professional, enterprise-ready  

### Role-Based Visual Difference (Same Palette)
**Support View** - Darker surfaces, higher data density, more tables, Navy dominant  
**Partner View** - Lighter surfaces, more whitespace, larger cards, Teal dominant

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
