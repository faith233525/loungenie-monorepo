# LounGenie Portal - Comprehensive Design Guide

**Version:** 1.7.0  
**Last Updated:** December 17, 2025  
**Design Philosophy:** Clean, modern, flat design with Primary Teal for actions and Soft Cyan for hover states

---

## Table of Contents

1. [Design Principles](#design-principles)
2. [Color Hierarchy & Palette](#color-hierarchy--palette)
3. [Typography System](#typography-system)
4. [Spacing & Layout](#spacing--layout)
5. [Component Library](#component-library)
6. [Icon System](#icon-system)
7. [Interactive States](#interactive-states)
8. [Mobile Responsiveness](#mobile-responsiveness)
9. [Performance Considerations](#performance-considerations)
10. [Implementation Examples](#implementation-examples)

---

## Design Principles

### 1. **Clean, Modern, Flat Design**
- ✅ No gradients or heavy textures
- ✅ Single-level elevation (subtle shadows only)
- ✅ Flat colors with strategic depth
- ✅ Crisp borders and clean lines
- ✅ Minimalist aesthetic

### 2. **Color Hierarchy**
- **Primary Teal (#0D9488):** Main actions, CTAs, primary buttons
- **Soft Cyan (#25D0EE):** Hover states, secondary highlights, interactive feedback
- **Status Colors:** Success (green), Warning (orange), Danger (red)
- **Neutrals:** Gray scale for text and surfaces

### 3. **Generous Whitespace**
- 8px spacing grid (4px → 64px)
- Adequate padding in cards (24px desktop, 16px mobile)
- Clear visual hierarchy through spacing
- Breathing room between elements prevents cognitive overload

### 4. **Consistent Iconography**
- FontAwesome 6.5.1 Free throughout
- Standardized sizes: sm (16px), md (20px), lg (24px), xl (32px)
- Semantic color coding for icon states

### 5. **Hover States Guide Interaction**
- Subtle color shifts indicate clickability
- Lift effects (2px translateY) on interactive elements
- Consistent 150-200ms transitions
- Clear active/pressed states

### 6. **Mobile-First Responsive**
- Cards stack single-column on mobile
- Tables scroll horizontally with touch-friendly controls
- Reduced padding and font sizes for small screens
- Touch targets minimum 40px height

---

## Color Hierarchy & Palette

### Primary Colors (Actions & CTAs)

```css
/* Primary Teal - Main Actions */
--lgp-color-brand: #0D9488           /* Primary action buttons, links, CTAs */
--lgp-color-brand-hover: #0F766E     /* Darker teal on hover */
--lgp-color-brand-light: #CCFBF1     /* Light teal background (subtle highlights) */
```

**Usage:**
- Primary buttons (Create, Save, Submit)
- Active navigation items
- Important links and CTAs
- Focus states on form inputs
- Icon accents for primary actions

**Example:**
```html
<button class="lgp-btn lgp-btn-primary">
    <i class="fa-solid fa-plus"></i>
    Create New Company
</button>
```

### Secondary Colors (Hover & Highlights)

```css
/* Soft Cyan - Secondary Highlights */
--lgp-color-accent: #25D0EE          /* Hover states, secondary highlights */
--lgp-color-accent-hover: #1CB5D1    /* Darker cyan on secondary hover */
--lgp-color-accent-light: #CFFAFE    /* Light cyan background (hover states) */
```

**Usage:**
- Table row hover backgrounds
- Secondary button hover states
- Card hover highlights
- Interactive element feedback
- Secondary navigation highlights

**Example:**
```css
.lgp-table tbody tr:hover {
    background-color: var(--lgp-color-accent-light);  /* Soft Cyan */
    border-color: var(--lgp-color-accent);
}
```

### Status Colors (WCAG AA Compliant)

```css
/* Success - Positive Actions/States */
--lgp-color-success: #16A34A         /* Green for success */
--lgp-color-success-light: #DCFCE7

/* Warning - Caution States */
--lgp-color-warning: #D97706         /* Orange for warnings */
--lgp-color-warning-light: #FEF3C7

/* Danger - Destructive Actions/Errors */
--lgp-color-danger: #DC2626          /* Red for errors/delete */
--lgp-color-danger-light: #FEE2E2

/* Info - Informational */
--lgp-color-info: #0D9488            /* Teal for info (matches brand) */
--lgp-color-info-light: #CCFBF1
```

**Status Badge Examples:**
```html
<span class="lgp-badge lgp-badge-success">Active</span>
<span class="lgp-badge lgp-badge-warning">Pending</span>
<span class="lgp-badge lgp-badge-danger">Inactive</span>
<span class="lgp-badge lgp-badge-info">New</span>
```

### Neutral Colors (Surfaces & Text)

```css
/* Surfaces */
--lgp-color-background: #FFFFFF      /* Page background */
--lgp-color-surface: #F7F8FA         /* Card/panel surface (light gray) */
--lgp-color-surface-raised: #FFFFFF  /* Elevated cards (white) */
--lgp-color-border: #E5E7EB          /* Default borders */
--lgp-color-border-hover: #D1D5DB    /* Border on hover */

/* Text Hierarchy */
--lgp-color-text-primary: #111827    /* Main text (near black) */
--lgp-color-text-secondary: #6B7280  /* Secondary text (medium gray) */
--lgp-color-text-tertiary: #9CA3AF   /* Tertiary/disabled (light gray) */
--lgp-color-text-inverse: #FFFFFF    /* Text on dark backgrounds */
```

### Color Usage Matrix

| Element | Primary Color | Hover Color | Background |
|---------|--------------|-------------|------------|
| **Primary Button** | Teal (#0D9488) | Dark Teal (#0F766E) | Teal |
| **Secondary Button** | Teal (border) | Soft Cyan (#25D0EE) | Transparent → Cyan Light |
| **Card Hover** | Teal (border) | — | White → Shadow |
| **Table Row Hover** | Soft Cyan (border) | — | Cyan Light (#CFFAFE) |
| **Link** | Teal | Dark Teal | Transparent |
| **Badge (Active)** | Success Green | — | Success Light |
| **Icon Accent** | Teal | — | Teal Light (circle) |

---

## Typography System

### Font Stack

```css
--lgp-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
                   "Helvetica Neue", Arial, sans-serif;
```

**System Font Benefits:**
- Fast loading (no web fonts)
- Native OS appearance
- Excellent readability
- Optimal rendering on all devices

### Font Size Scale

```css
--lgp-font-size-xs: 0.75rem      /* 12px - Small labels, badges */
--lgp-font-size-sm: 0.875rem     /* 14px - Body text, table content */
--lgp-font-size-base: 1rem       /* 16px - Base font size */
--lgp-font-size-lg: 1.125rem     /* 18px - Card titles, section headers */
--lgp-font-size-xl: 1.25rem      /* 20px - Page section titles */
--lgp-font-size-2xl: 1.5rem      /* 24px - Page titles, stat values */
```

### Font Weights

```css
--lgp-font-weight-regular: 400    /* Body text */
--lgp-font-weight-medium: 500     /* Emphasized text */
--lgp-font-weight-semibold: 600   /* Headings, labels */
--lgp-font-weight-bold: 700       /* Strong emphasis */
```

### Line Heights

```css
--lgp-line-height-tight: 1.25     /* Headings */
--lgp-line-height-normal: 1.5     /* Body text */
--lgp-line-height-relaxed: 1.625  /* Long-form content */
```

### Typography Hierarchy

| Element | Size | Weight | Line Height | Usage |
|---------|------|--------|-------------|-------|
| **Page Title** | 2xl (24px) | Semibold (600) | Tight (1.25) | Dashboard, main page headings |
| **Section Header** | xl (20px) | Semibold (600) | Tight (1.25) | Card headers, section dividers |
| **Card Title** | lg (18px) | Semibold (600) | Tight (1.25) | Unit names, company names |
| **Body Text** | base (16px) | Regular (400) | Normal (1.5) | Descriptions, content |
| **Small Text** | sm (14px) | Regular (400) | Normal (1.5) | Table content, metadata |
| **Label/Badge** | xs (12px) | Medium (500) | Normal (1.5) | Status badges, small labels |

**Example:**
```html
<h1 style="font-size: var(--lgp-font-size-2xl); font-weight: var(--lgp-font-weight-semibold);">
    Dashboard
</h1>
<p class="lgp-text-sm lgp-text-muted">Manage your companies and units</p>
```

---

## Spacing & Layout

### 8px Spacing Grid

```css
--lgp-space-0: 0
--lgp-space-1: 0.25rem   /* 4px - Tight spacing */
--lgp-space-2: 0.5rem    /* 8px - Base unit */
--lgp-space-3: 0.75rem   /* 12px - Small gaps */
--lgp-space-4: 1rem      /* 16px - Default spacing */
--lgp-space-5: 1.25rem   /* 20px - Medium spacing */
--lgp-space-6: 1.5rem    /* 24px - Card padding */
--lgp-space-8: 2rem      /* 32px - Section spacing */
--lgp-space-10: 2.5rem   /* 40px - Large spacing */
--lgp-space-12: 3rem     /* 48px - Extra large */
--lgp-space-16: 4rem     /* 64px - Page sections */
```

### Spacing Guidelines

**Card Padding:**
- Desktop: 24px (--lgp-space-6)
- Mobile: 16px (--lgp-space-4)

**Gaps Between Cards:**
- Desktop: 24px (--lgp-space-6)
- Mobile: 16px (--lgp-space-4)

**Table Cell Padding:**
- Desktop: 16px (--lgp-space-4)
- Mobile: 8px horizontal, 12px vertical

**Section Spacing:**
- Between major sections: 64px (--lgp-space-16)
- Between subsections: 32px (--lgp-space-8)
- Between related items: 16px (--lgp-space-4)

### Utility Classes

```html
<!-- Margin utilities -->
<div class="lgp-mt-4">Margin top 16px</div>
<div class="lgp-mb-4">Margin bottom 16px</div>
<div class="lgp-mx-4">Horizontal margin 16px</div>
<div class="lgp-my-4">Vertical margin 16px</div>

<!-- Padding utilities -->
<div class="lgp-pt-4">Padding top 16px</div>
<div class="lgp-pb-4">Padding bottom 16px</div>
<div class="lgp-px-4">Horizontal padding 16px</div>
<div class="lgp-py-4">Vertical padding 16px</div>

<!-- Flexbox utilities -->
<div class="lgp-flex lgp-items-center lgp-gap-4">
    <span>Item 1</span>
    <span>Item 2</span>
</div>
```

---

## Component Library

### 1. Cards (Units, Tickets, Companies)

**Card Structure:**
```html
<div class="lgp-card lgp-card-clickable" onclick="handleClick()">
    <div class="lgp-card-header">
        <div class="lgp-card-header-left">
            <div class="lgp-card-icon">
                <i class="fa-solid fa-building"></i>
            </div>
            <div>
                <h3 class="lgp-card-title">Acme Properties</h3>
                <p class="lgp-card-subtitle">247 Units • 186 Active</p>
            </div>
        </div>
        <span class="lgp-badge lgp-badge-success">Active</span>
    </div>
    
    <div class="lgp-card-body">
        <div class="lgp-flex lgp-gap-4 lgp-mb-4">
            <div>
                <div class="lgp-text-muted lgp-text-sm">Venue Type</div>
                <div class="lgp-font-semibold">Resort</div>
            </div>
            <div>
                <div class="lgp-text-muted lgp-text-sm">Open Tickets</div>
                <div class="lgp-font-semibold">12</div>
            </div>
        </div>
    </div>
    
    <div class="lgp-card-footer">
        <span class="lgp-text-muted lgp-text-sm">
            <i class="fa-solid fa-clock"></i>
            Updated 2 hours ago
        </span>
        <a href="#" class="lgp-link">
            View Details <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</div>
```

**Card Grid:**
```html
<div class="lgp-card-grid">
    <!-- Cards auto-arrange: 320px min, responsive -->
    <div class="lgp-card">...</div>
    <div class="lgp-card">...</div>
    <div class="lgp-card">...</div>
</div>
```

**Card States:**
- Default: White background, subtle shadow
- Hover: Teal border, lift 2px, shadow increase
- Active: No lift, shadow returns to default
- Mobile: Single column, reduced padding

### 2. Tables (List Views)

**Enhanced Table:**
```html
<div class="lgp-table-wrapper">
    <table class="lgp-table">
        <thead>
            <tr>
                <th>Unit Name</th>
                <th>Status</th>
                <th>Company</th>
                <th class="lgp-table-numeric">Unit #</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr onclick="handleRowClick()">
                <td>
                    <div class="lgp-flex lgp-items-center lgp-gap-2">
                        <i class="fa-solid fa-home lgp-icon-brand"></i>
                        Unit 101
                    </div>
                </td>
                <td><span class="lgp-badge lgp-badge-success">Active</span></td>
                <td>Acme Properties</td>
                <td class="lgp-table-numeric">101</td>
                <td>
                    <div class="lgp-table-actions">
                        <a href="#" onclick="event.stopPropagation()">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                        <a href="#" onclick="event.stopPropagation()">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

**Table Features:**
- Sticky header (stays visible on scroll)
- Zebra striping (even rows slightly darker)
- Hover: Soft Cyan background (#CFFAFE)
- Mobile: Horizontal scroll with min-width 600px
- Numeric columns: Right-aligned, tabular numbers

### 3. Buttons

**Button Variants:**

```html
<!-- Primary (Teal) - Main actions -->
<button class="lgp-btn lgp-btn-primary">
    <i class="fa-solid fa-plus"></i>
    Create New
</button>

<!-- Secondary (Teal outline) - Cancel, back -->
<button class="lgp-btn lgp-btn-secondary">
    <i class="fa-solid fa-arrow-left"></i>
    Go Back
</button>

<!-- Danger (Red) - Delete, destructive -->
<button class="lgp-btn lgp-btn-danger">
    <i class="fa-solid fa-trash"></i>
    Delete
</button>

<!-- Success (Green) - Save, complete -->
<button class="lgp-btn lgp-btn-success">
    <i class="fa-solid fa-check"></i>
    Save Changes
</button>

<!-- Ghost - Subtle actions -->
<button class="lgp-btn lgp-btn-ghost">
    <i class="fa-solid fa-ellipsis-vertical"></i>
</button>

<!-- Icon button -->
<button class="lgp-btn lgp-btn-icon lgp-btn-primary">
    <i class="fa-solid fa-search"></i>
</button>

<!-- Sizes -->
<button class="lgp-btn lgp-btn-sm lgp-btn-secondary">Small</button>
<button class="lgp-btn lgp-btn-lg lgp-btn-primary">Large CTA</button>
```

**Button States:**
- Default: Solid color, 44px min height
- Hover: Lift 2px, shadow increase, color darkens
- Active: Press down 1px
- Disabled: 50% opacity, no pointer events
- Focus: Teal focus ring (3px offset)

### 4. Status Badges

```html
<span class="lgp-badge lgp-badge-success">
    <i class="fa-solid fa-check"></i>
    Active
</span>
<span class="lgp-badge lgp-badge-warning">Pending</span>
<span class="lgp-badge lgp-badge-danger">Inactive</span>
<span class="lgp-badge lgp-badge-info">New</span>
```

**Badge Usage:**
- Unit status: active/inactive/maintenance
- Ticket priority: low/medium/high/urgent
- Request status: open/in-progress/resolved/closed
- Payment status: paid/unpaid/overdue

### 5. Dashboard Stat Cards

```html
<div class="lgp-card-grid">
    <div class="lgp-stat-card">
        <div class="lgp-stat-card-header">
            <div class="lgp-stat-card-icon">
                <i class="fa-solid fa-building"></i>
            </div>
        </div>
        <div class="lgp-stat-card-value">247</div>
        <div class="lgp-stat-card-label">Total Companies</div>
        <div class="lgp-stat-card-trend trend-up">
            <i class="fa-solid fa-arrow-up"></i>
            +12% from last month
        </div>
    </div>
</div>
```

### 6. Empty States

```html
<div class="lgp-empty-state">
    <div class="lgp-empty-state-icon">
        <i class="fa-solid fa-inbox" style="font-size: 64px;"></i>
    </div>
    <h3 class="lgp-empty-state-title">No tickets found</h3>
    <p class="lgp-empty-state-description">
        There are no tickets matching your current filters. 
        Try adjusting your search or create a new ticket.
    </p>
    <button class="lgp-btn lgp-btn-primary">
        <i class="fa-solid fa-plus"></i>
        Create Ticket
    </button>
</div>
```

### 7. Loading Spinner

```html
<span class="lgp-spinner"></span>
<span class="lgp-spinner lgp-spinner-lg"></span>

<!-- In button -->
<button class="lgp-btn lgp-btn-primary" disabled>
    <span class="lgp-spinner"></span>
    Loading...
</button>
```

### 8. Avatars

```html
<!-- Initials -->
<div class="lgp-avatar">JD</div>

<!-- Image -->
<div class="lgp-avatar">
    <img src="user.jpg" alt="John Doe">
</div>

<!-- Sizes -->
<div class="lgp-avatar lgp-avatar-sm">JD</div>
<div class="lgp-avatar lgp-avatar-lg">JD</div>

<!-- In context -->
<div class="lgp-flex lgp-items-center lgp-gap-2">
    <div class="lgp-avatar">JD</div>
    <div>
        <div class="lgp-font-semibold">John Doe</div>
        <div class="lgp-text-sm lgp-text-muted">Partner</div>
    </div>
</div>
```

---

## Icon System

### FontAwesome 6.5.1 Free

**Automatically loaded via CDN:**
```html
<!-- Already included in class-lgp-assets.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
```

### Icon Sizes

```html
<i class="fa-solid fa-home lgp-icon lgp-icon-sm"></i>  <!-- 16px -->
<i class="fa-solid fa-home lgp-icon lgp-icon-md"></i>  <!-- 20px -->
<i class="fa-solid fa-home lgp-icon lgp-icon-lg"></i>  <!-- 24px -->
<i class="fa-solid fa-home lgp-icon lgp-icon-xl"></i>  <!-- 32px -->
```

### Icon Colors

```html
<i class="fa-solid fa-check lgp-icon-success"></i>      <!-- Green -->
<i class="fa-solid fa-exclamation-triangle lgp-icon-warning"></i>  <!-- Orange -->
<i class="fa-solid fa-times lgp-icon-danger"></i>       <!-- Red -->
<i class="fa-solid fa-info-circle lgp-icon-brand"></i>  <!-- Teal -->
<i class="fa-solid fa-question lgp-icon-muted"></i>     <!-- Gray -->
```

### Recommended Icon Library

| Category | Icons |
|----------|-------|
| **Companies** | `fa-building`, `fa-briefcase`, `fa-city` |
| **Units** | `fa-home`, `fa-door-open`, `fa-bed` |
| **Tickets** | `fa-ticket`, `fa-clipboard-list`, `fa-tags` |
| **Service** | `fa-wrench`, `fa-tools`, `fa-screwdriver` |
| **Gateways** | `fa-network-wired`, `fa-phone`, `fa-satellite-dish` |
| **Training** | `fa-graduation-cap`, `fa-book`, `fa-video` |
| **Users** | `fa-user`, `fa-users`, `fa-user-circle` |
| **Actions** | `fa-eye`, `fa-pen`, `fa-trash`, `fa-plus`, `fa-check`, `fa-xmark` |
| **Navigation** | `fa-arrow-left`, `fa-arrow-right`, `fa-chevron-down`, `fa-bars` |
| **Status** | `fa-check-circle`, `fa-exclamation-circle`, `fa-times-circle` |
| **Time** | `fa-clock`, `fa-calendar`, `fa-calendar-check` |
| **Settings** | `fa-cog`, `fa-sliders`, `fa-gear` |
| **Search/Filter** | `fa-search`, `fa-magnifying-glass`, `fa-filter` |

**Browse all icons:** https://fontawesome.com/icons

---

## Interactive States

### Hover States

All interactive elements have hover feedback:

**Cards:**
```css
/* Default → Hover */
border: 1px solid #E5E7EB → border: 1px solid #0D9488 (Teal)
transform: translateY(0) → transform: translateY(-2px)
box-shadow: sm → box-shadow: md
```

**Buttons:**
```css
/* Primary Button: Default → Hover */
background: #0D9488 (Teal) → background: #0F766E (Darker Teal)
transform: translateY(0) → transform: translateY(-2px)
box-shadow: none → box-shadow: md

/* Secondary Button: Default → Hover */
background: transparent → background: #CFFAFE (Cyan Light)
border: #0D9488 (Teal) → border: #25D0EE (Soft Cyan)
color: #0D9488 → color: #1CB5D1
```

**Table Rows:**
```css
/* Default → Hover */
background: white → background: #CFFAFE (Cyan Light)
border: #E5E7EB → border: #25D0EE (Soft Cyan)
```

**Links:**
```css
/* Default → Hover */
color: #0D9488 (Teal) → color: #0F766E (Darker Teal)
text-decoration: none → text-decoration: underline (optional)
```

### Focus States

All interactive elements have keyboard focus indicators:

```css
.lgp-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px #CCFBF1;  /* Teal light ring */
}
```

### Active/Pressed States

Buttons have active feedback:

```css
.lgp-btn:active {
    transform: translateY(1px);  /* Press down effect */
}
```

### Disabled States

Disabled elements are visually muted:

```css
.lgp-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
```

### Transition Speeds

```css
--lgp-transition-fast: 150ms ease-in-out    /* Links, small elements */
--lgp-transition-base: 200ms ease-in-out    /* Cards, buttons */
--lgp-transition-slow: 300ms ease-in-out    /* Modals, large animations */
```

---

## Mobile Responsiveness

### Breakpoints

```css
/* Mobile devices */
@media (max-width: 768px) {
    /* Styles for mobile */
}
```

### Mobile Optimizations

**Card Grid:**
- Desktop: Auto-fill grid, 320px minimum
- Mobile: Single column, reduced gap (16px)
- Padding: 24px → 16px
- Card header: Stacks vertically

**Tables:**
- Mobile: Horizontal scroll enabled
- Min-width: 600px (prevents collapse)
- Font size: 16px → 12px
- Cell padding: 16px → 8px horizontal, 12px vertical
- Touch-friendly scroll with momentum

**Buttons:**
- Min height: 44px → 40px (still accessible)
- Large buttons: 52px → 48px
- Text size slightly reduced for mobile

**Typography:**
- Base font: 16px → 14px
- Stat values: 24px → 20px
- Maintains readability at reduced sizes

**Spacing:**
- Card padding: 24px → 16px
- Card gaps: 24px → 16px
- Empty state padding: 64px → 32px

### Touch Targets

Minimum sizes for mobile accessibility:
- Buttons: 40px height
- Links with icons: 40px touch area
- Table action buttons: 40px × 40px
- Card click area: Full card

### Mobile-Specific Features

**Smooth Scrolling:**
```css
.lgp-table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;  /* iOS momentum scrolling */
}
```

**Reduced Motion Support:**
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## Performance Considerations

### CSS Optimizations

1. **No Web Fonts:** System font stack loads instantly
2. **Minimal Shadows:** Single-level elevation only
3. **Hardware Acceleration:** `transform` for animations (GPU-accelerated)
4. **Efficient Transitions:** Only animate `transform` and `opacity`

### Icon Loading

FontAwesome loaded via CDN with caching:
```html
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      crossorigin="anonymous">
```

### Avoiding Performance Pitfalls

❌ **Don't:**
- Animate `width`, `height`, `left`, `right` (causes reflow)
- Use complex `box-shadow` on many elements
- Add heavy gradients or patterns
- Overuse transforms on scroll

✅ **Do:**
- Animate `transform` and `opacity` only
- Use simple shadows (sm/md only)
- Keep flat colors
- Limit animations to user interactions

### Shared Hosting Compatibility

- No heavy JavaScript frameworks required
- Pure CSS components (minimal JS)
- System fonts (no font downloads)
- Efficient selectors (no deep nesting)
- CDN-hosted icon library (cached globally)

---

## Implementation Examples

### Example 1: Company List (Card Grid)

```html
<div class="lgp-portal">
    <div style="padding: var(--lgp-space-8);">
        <h1 style="font-size: var(--lgp-font-size-2xl); 
                   font-weight: var(--lgp-font-weight-semibold);
                   margin-bottom: var(--lgp-space-2);">
            Companies
        </h1>
        <p class="lgp-text-muted lgp-mb-4">
            Manage your partner companies and their properties
        </p>
        
        <div class="lgp-card-grid">
            <!-- Company Card -->
            <div class="lgp-card lgp-card-clickable" 
                 onclick="window.location='/portal/company/123'">
                <div class="lgp-card-header">
                    <div class="lgp-card-header-left">
                        <div class="lgp-card-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <h3 class="lgp-card-title">Acme Properties</h3>
                            <p class="lgp-card-subtitle">247 Units • 186 Active</p>
                        </div>
                    </div>
                    <span class="lgp-badge lgp-badge-success">
                        <i class="fa-solid fa-check"></i>
                        Active
                    </span>
                </div>
                
                <div class="lgp-card-body">
                    <div class="lgp-flex lgp-gap-4 lgp-mb-4">
                        <div>
                            <div class="lgp-text-muted lgp-text-sm">Venue Type</div>
                            <div class="lgp-font-semibold">Resort</div>
                        </div>
                        <div>
                            <div class="lgp-text-muted lgp-text-sm">Open Tickets</div>
                            <div class="lgp-font-semibold">12</div>
                        </div>
                        <div>
                            <div class="lgp-text-muted lgp-text-sm">Units</div>
                            <div class="lgp-font-semibold">247</div>
                        </div>
                    </div>
                </div>
                
                <div class="lgp-card-footer">
                    <span class="lgp-text-muted lgp-text-sm">
                        <i class="fa-solid fa-clock"></i>
                        Updated 2 hours ago
                    </span>
                    <a href="/portal/company/123" class="lgp-link">
                        View Details
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- More cards... -->
        </div>
    </div>
</div>
```

### Example 2: Ticket Table

```html
<div class="lgp-table-wrapper">
    <table class="lgp-table">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th class="lgp-table-numeric">Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr onclick="window.location='/portal/ticket/456'">
                <td>
                    <div class="lgp-flex lgp-items-center lgp-gap-2">
                        <i class="fa-solid fa-ticket lgp-icon-brand"></i>
                        #456
                    </div>
                </td>
                <td>Pool pump not working</td>
                <td><span class="lgp-badge lgp-badge-danger">Urgent</span></td>
                <td><span class="lgp-badge lgp-badge-warning">In Progress</span></td>
                <td>
                    <div class="lgp-flex lgp-items-center lgp-gap-2">
                        <div class="lgp-avatar lgp-avatar-sm">JD</div>
                        John Doe
                    </div>
                </td>
                <td class="lgp-table-numeric">2h ago</td>
                <td>
                    <div class="lgp-table-actions">
                        <a href="/portal/ticket/456" onclick="event.stopPropagation()">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                        <a href="/portal/ticket/456/edit" onclick="event.stopPropagation()">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Example 3: Dashboard with Stat Cards

```html
<div class="lgp-portal">
    <div style="padding: var(--lgp-space-8);">
        <h1 style="font-size: var(--lgp-font-size-2xl); 
                   font-weight: var(--lgp-font-weight-semibold);
                   margin-bottom: var(--lgp-space-6);">
            Dashboard
        </h1>
        
        <!-- Stat Cards Grid -->
        <div class="lgp-card-grid lgp-mb-4">
            <div class="lgp-stat-card">
                <div class="lgp-stat-card-header">
                    <div class="lgp-stat-card-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                </div>
                <div class="lgp-stat-card-value">247</div>
                <div class="lgp-stat-card-label">Total Companies</div>
                <div class="lgp-stat-card-trend trend-up">
                    <i class="fa-solid fa-arrow-up"></i>
                    +12% from last month
                </div>
            </div>
            
            <div class="lgp-stat-card">
                <div class="lgp-stat-card-header">
                    <div class="lgp-stat-card-icon">
                        <i class="fa-solid fa-home"></i>
                    </div>
                </div>
                <div class="lgp-stat-card-value">1,543</div>
                <div class="lgp-stat-card-label">Active Units</div>
                <div class="lgp-stat-card-trend trend-up">
                    <i class="fa-solid fa-arrow-up"></i>
                    +5% from last month
                </div>
            </div>
            
            <div class="lgp-stat-card">
                <div class="lgp-stat-card-header">
                    <div class="lgp-stat-card-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                </div>
                <div class="lgp-stat-card-value">23</div>
                <div class="lgp-stat-card-label">Open Tickets</div>
                <div class="lgp-stat-card-trend trend-down">
                    <i class="fa-solid fa-arrow-down"></i>
                    -15% from last month
                </div>
            </div>
            
            <div class="lgp-stat-card">
                <div class="lgp-stat-card-header">
                    <div class="lgp-stat-card-icon">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                </div>
                <div class="lgp-stat-card-value">89</div>
                <div class="lgp-stat-card-label">Pending Service</div>
                <div class="lgp-stat-card-trend trend-up">
                    <i class="fa-solid fa-arrow-up"></i>
                    +8% from last month
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Table -->
        <h2 style="font-size: var(--lgp-font-size-xl); 
                   font-weight: var(--lgp-font-weight-semibold);
                   margin-bottom: var(--lgp-space-4);">
            Recent Activity
        </h2>
        
        <div class="lgp-table-wrapper">
            <!-- Table content here -->
        </div>
    </div>
</div>
```

---

## Best Practices Checklist

### ✅ Color Usage
- [ ] Primary Teal (#0D9488) for main actions and CTAs
- [ ] Soft Cyan (#25D0EE) for hover states and secondary highlights
- [ ] Status colors match semantic meaning (green=success, red=danger)
- [ ] Text maintains WCAG AA contrast (4.5:1 minimum)
- [ ] Avoid overusing accent colors (use strategically)

### ✅ Typography
- [ ] Use system font stack (no web fonts)
- [ ] Font sizes follow scale (xs/sm/base/lg/xl/2xl)
- [ ] Semibold weight for headings, regular for body
- [ ] Line height appropriate (tight for headings, normal for body)

### ✅ Spacing
- [ ] Follow 8px grid for all spacing
- [ ] Cards have 24px padding (16px on mobile)
- [ ] Adequate whitespace between sections
- [ ] Consistent gap spacing in grids (24px desktop, 16px mobile)

### ✅ Components
- [ ] Cards use `.lgp-card` with icon, title, subtitle, body, footer
- [ ] Tables use `.lgp-table-wrapper` with hover states
- [ ] Buttons have appropriate variant (primary/secondary/danger/success)
- [ ] Status badges match state (success/warning/danger/info)
- [ ] Icons from FontAwesome with consistent sizing

### ✅ Interactive States
- [ ] All clickable elements have hover effects
- [ ] Hover transitions are smooth (150-200ms)
- [ ] Focus states visible for keyboard navigation
- [ ] Active/pressed states provide feedback
- [ ] Disabled states are clearly indicated

### ✅ Mobile Responsiveness
- [ ] Cards stack single-column on mobile
- [ ] Tables scroll horizontally on mobile
- [ ] Touch targets minimum 40px
- [ ] Reduced padding/spacing on small screens
- [ ] Text remains readable (minimum 14px)

### ✅ Performance
- [ ] System fonts only (no web font downloads)
- [ ] Animate `transform` and `opacity` only
- [ ] Simple shadows (no complex effects)
- [ ] Icons loaded via CDN (cached)
- [ ] Reduced motion support included

---

## Anti-Patterns to Avoid

### ❌ Don't Do This:

**Color Misuse:**
```html
<!-- Don't use Soft Cyan for primary buttons -->
<button style="background: #25D0EE;">Create</button>

<!-- Use Teal for primary actions -->
<button class="lgp-btn lgp-btn-primary">Create</button>
```

**Spacing Inconsistency:**
```html
<!-- Don't use arbitrary spacing -->
<div style="margin-bottom: 17px;">

<!-- Use spacing scale -->
<div class="lgp-mb-4">  <!-- 16px -->
```

**Heavy Effects:**
```css
/* Don't use gradients or complex shadows */
.card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

/* Keep it flat with simple shadows */
.lgp-card {
    background: #FFFFFF;
    box-shadow: var(--lgp-shadow-sm);
}
```

**Icon Inconsistency:**
```html
<!-- Don't mix icon libraries -->
<i class="fa-solid fa-home"></i>
<span class="material-icons">home</span>

<!-- Use FontAwesome consistently -->
<i class="fa-solid fa-home"></i>
<i class="fa-solid fa-building"></i>
```

**Poor Mobile UX:**
```css
/* Don't make touch targets too small */
.mobile-button {
    height: 28px;  /* Too small! */
}

/* Minimum 40px for mobile */
.lgp-btn {
    min-height: 40px;
}
```

---

## Migration Guide (From Old to New)

### Color Updates

| Old Blue | New Teal | Usage |
|----------|----------|-------|
| `#3B82F6` | `#0D9488` | Primary buttons, main actions |
| `#2563EB` | `#0F766E` | Hover states for primary |
| `#DBEAFE` | `#CCFBF1` | Light backgrounds, subtle highlights |
| N/A | `#25D0EE` | **New:** Soft Cyan for hover/secondary |
| N/A | `#CFFAFE` | **New:** Cyan Light for hover backgrounds |

### Component Class Changes

No breaking changes! All existing `.lgp-*` classes remain compatible.

**New classes added:**
- `.lgp-accent` - Soft Cyan text color
- `.lgp-accent-bg` - Soft Cyan background
- `.lgp-accent-border` - Soft Cyan border
- `.lgp-hover-accent:hover` - Cyan hover state utility

### Template Updates

**Step 1:** Cards already use new colors automatically via CSS variables

**Step 2:** Tables automatically use Soft Cyan for hover (CSS updates applied)

**Step 3:** Secondary buttons now have Cyan hover (CSS updates applied)

**Step 4:** No template changes required! All updates are CSS-only.

---

## Quick Reference Card

```
┌─────────────────────────────────────────────────────────┐
│ LounGenie Portal Design System - Quick Reference        │
├─────────────────────────────────────────────────────────┤
│ PRIMARY COLORS                                          │
│  • Actions/CTAs:     #0D9488 (Teal)                    │
│  • Hover/Secondary:  #25D0EE (Soft Cyan)               │
│  • Success:          #16A34A (Green)                    │
│  • Warning:          #D97706 (Orange)                   │
│  • Danger:           #DC2626 (Red)                      │
├─────────────────────────────────────────────────────────┤
│ SPACING (8px grid)                                      │
│  • Card padding:     24px (desktop), 16px (mobile)     │
│  • Card gap:         24px (desktop), 16px (mobile)     │
│  • Section gap:      32px-64px                          │
├─────────────────────────────────────────────────────────┤
│ TYPOGRAPHY                                              │
│  • Page title:       24px/600 (2xl/semibold)          │
│  • Section header:   20px/600 (xl/semibold)           │
│  • Card title:       18px/600 (lg/semibold)           │
│  • Body text:        16px/400 (base/regular)          │
│  • Small text:       14px/400 (sm/regular)            │
│  • Badge/label:      12px/500 (xs/medium)             │
├─────────────────────────────────────────────────────────┤
│ COMPONENTS                                              │
│  • Cards:            .lgp-card with hover lift         │
│  • Tables:           .lgp-table with Cyan hover        │
│  • Buttons:          .lgp-btn-primary (Teal)          │
│  • Badges:           .lgp-badge-{variant}              │
│  • Icons:            FontAwesome 6.5.1                 │
├─────────────────────────────────────────────────────────┤
│ HOVER EFFECTS                                           │
│  • Cards:            Teal border + lift 2px            │
│  • Buttons:          Darken + lift 2px                 │
│  • Table rows:       Cyan Light background             │
│  • Links:            Darken color                      │
│  • Transition:       150-200ms                         │
├─────────────────────────────────────────────────────────┤
│ MOBILE (< 768px)                                        │
│  • Cards:            Single column stack               │
│  • Tables:           Horizontal scroll                 │
│  • Touch targets:    Minimum 40px                      │
│  • Padding:          Reduced 30-40%                    │
└─────────────────────────────────────────────────────────┘
```

---

## Support & Resources

**Design Tokens CSS:**  
[/loungenie-portal/assets/css/design-tokens.css](../loungenie-portal/assets/css/design-tokens.css)

**Icon Library:**  
[FontAwesome 6.5.1 Free](https://fontawesome.com/icons)

**Implementation Examples:**  
See [DESIGN_SYSTEM_GUIDE.md](DESIGN_SYSTEM_GUIDE.md) for component markup

**Color Accessibility:**  
All colors meet WCAG AA standards (4.5:1 contrast minimum)

**Browser Support:**  
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

**Version History:**
- v1.7.0 (Dec 17, 2025): Updated to Primary Teal + Soft Cyan color scheme, added mobile optimizations
- v1.6.0 (Dec 17, 2025): Initial modern flat design system

**Maintained by:** LounGenie Development Team  
**Last Review:** December 17, 2025
