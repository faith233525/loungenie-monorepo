# Portal Design System - Implementation Guide

**Version:** 1.6.0  
**Last Updated:** December 17, 2025  
**Design Philosophy:** Clean, modern, flat design with generous whitespace

---

## Design Principles

### 1. **Clean, Modern, Flat Design**
- No gradients or heavy textures
- Flat colors with subtle shadows for depth
- Single-level elevation system (no multi-layered shadows)
- Crisp borders and clean lines

### 2. **Whitespace is Essential**
- 8px spacing grid for consistency
- Generous padding in cards (24px)
- Clear visual hierarchy through spacing
- Adequate breathing room between elements

### 3. **Consistent Iconography**
- **Icon Library:** FontAwesome 6.5.1 (Free)
- Automatically loaded via CDN
- Use `<i class="fa-solid fa-icon-name"></i>` for icons
- Alternative: Material Icons (if preferred)

### 4. **Hover States Guide User Interaction**
- Cards: Subtle lift + border color change
- Buttons: Lift 2px + shadow increase
- Table rows: Background color change to brand light
- Links: Color shift to darker brand

---

## Component Library

### Cards (Units, Tickets, Companies)

**Basic Card Structure:**
```html
<div class="lgp-card lgp-card-clickable" onclick="handleCardClick()">
    <div class="lgp-card-header">
        <div class="lgp-card-header-left">
            <div class="lgp-card-icon">
                <i class="fa-solid fa-building"></i>
            </div>
            <div>
                <h3 class="lgp-card-title">Company Name</h3>
                <p class="lgp-card-subtitle">123 Units • 45 Active</p>
            </div>
        </div>
        <span class="lgp-badge lgp-badge-success">Active</span>
    </div>
    
    <div class="lgp-card-body">
        <p>Additional details and description text here.</p>
    </div>
    
    <div class="lgp-card-footer">
        <span class="lgp-text-muted lgp-text-sm">Last updated: 2 hours ago</span>
        <a href="#" class="lgp-link">View Details →</a>
    </div>
</div>
```

**Card Grid Layout:**
```html
<div class="lgp-card-grid">
    <!-- Cards auto-flow in responsive grid (320px min, auto-fill) -->
    <div class="lgp-card">...</div>
    <div class="lgp-card">...</div>
    <div class="lgp-card">...</div>
</div>
```

**Hover Behavior:**
- Border color changes to brand blue
- Card lifts 2px (translateY)
- Shadow increases from sm to md
- Smooth 200ms transition

---

### Tables (List Views)

**Enhanced Table Structure:**
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
            <tr>
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
                        <a href="#"><i class="fa-solid fa-eye"></i> View</a>
                        <a href="#"><i class="fa-solid fa-pen"></i> Edit</a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

**Hover Behavior:**
- Row background changes to brand-light (#DBEAFE)
- Border color changes to brand blue
- Cursor changes to pointer
- 150ms fast transition

**Table Features:**
- Sticky header (stays visible on scroll)
- Zebra striping (even rows slightly darker)
- Numeric column alignment (right-aligned)
- Action buttons with hover states

---

### Buttons

**Button Variants:**
```html
<!-- Primary (main actions) -->
<button class="lgp-btn lgp-btn-primary">
    <i class="fa-solid fa-plus"></i>
    Create New
</button>

<!-- Secondary (cancel, back) -->
<button class="lgp-btn lgp-btn-secondary">
    <i class="fa-solid fa-arrow-left"></i>
    Go Back
</button>

<!-- Danger (delete, destructive) -->
<button class="lgp-btn lgp-btn-danger">
    <i class="fa-solid fa-trash"></i>
    Delete
</button>

<!-- Success (save, complete) -->
<button class="lgp-btn lgp-btn-success">
    <i class="fa-solid fa-check"></i>
    Save Changes
</button>

<!-- Ghost (subtle actions) -->
<button class="lgp-btn lgp-btn-ghost">
    <i class="fa-solid fa-ellipsis-vertical"></i>
</button>

<!-- Icon-only button -->
<button class="lgp-btn lgp-btn-icon lgp-btn-primary">
    <i class="fa-solid fa-search"></i>
</button>

<!-- Small button -->
<button class="lgp-btn lgp-btn-sm lgp-btn-secondary">Small Action</button>

<!-- Large button -->
<button class="lgp-btn lgp-btn-lg lgp-btn-primary">Large CTA</button>
```

**Hover Behavior:**
- Lifts 2px (translateY)
- Shadow increases
- Active state: Presses down 1px
- Disabled: 50% opacity, no pointer events

---

### Status Badges

**Badge Types:**
```html
<span class="lgp-badge lgp-badge-success">Active</span>
<span class="lgp-badge lgp-badge-warning">Pending</span>
<span class="lgp-badge lgp-badge-danger">Inactive</span>
<span class="lgp-badge lgp-badge-info">New</span>
```

**With Icons:**
```html
<span class="lgp-badge lgp-badge-success">
    <i class="fa-solid fa-check"></i>
    Active
</span>
```

**Usage:**
- Unit status: active/inactive/pending
- Ticket priority: low/medium/high/urgent
- Request status: open/in-progress/resolved/closed
- Payment status: paid/unpaid/overdue

---

### Stat Cards (Dashboard Metrics)

**Stat Card Structure:**
```html
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
```

**Grid Layout:**
```html
<div class="lgp-card-grid">
    <div class="lgp-stat-card">...</div>
    <div class="lgp-stat-card">...</div>
    <div class="lgp-stat-card">...</div>
    <div class="lgp-stat-card">...</div>
</div>
```

**Hover Behavior:**
- Card lifts 2px
- Border changes to brand color
- Shadow increases

---

### Icons

**Icon Sizes:**
```html
<i class="fa-solid fa-home lgp-icon lgp-icon-sm"></i>  <!-- 16px -->
<i class="fa-solid fa-home lgp-icon lgp-icon-md"></i>  <!-- 20px (default) -->
<i class="fa-solid fa-home lgp-icon lgp-icon-lg"></i>  <!-- 24px -->
<i class="fa-solid fa-home lgp-icon lgp-icon-xl"></i>  <!-- 32px -->
```

**Icon Colors:**
```html
<i class="fa-solid fa-check lgp-icon-success"></i>
<i class="fa-solid fa-exclamation-triangle lgp-icon-warning"></i>
<i class="fa-solid fa-times lgp-icon-danger"></i>
<i class="fa-solid fa-info-circle lgp-icon-brand"></i>
<i class="fa-solid fa-question lgp-icon-muted"></i>
```

**Recommended Icons:**
- Companies: `fa-building`, `fa-briefcase`
- Units: `fa-home`, `fa-door-open`
- Tickets: `fa-ticket`, `fa-clipboard-list`
- Service Requests: `fa-wrench`, `fa-tools`
- Gateways: `fa-network-wired`, `fa-phone`
- Training: `fa-graduation-cap`, `fa-book`
- Users: `fa-user`, `fa-users`
- Settings: `fa-cog`, `fa-sliders`
- Search: `fa-search`, `fa-magnifying-glass`
- Filter: `fa-filter`, `fa-funnel`
- Edit: `fa-pen`, `fa-pencil`
- Delete: `fa-trash`, `fa-trash-can`
- View: `fa-eye`, `fa-eye-slash`
- Save: `fa-floppy-disk`, `fa-check`
- Cancel: `fa-xmark`, `fa-ban`

---

### Links

**Link Styles:**
```html
<!-- Basic link (no underline, hover color change) -->
<a href="#" class="lgp-link">View Details</a>

<!-- Link with underline on hover -->
<a href="#" class="lgp-link lgp-link-underline">Read More</a>

<!-- Link with icon -->
<a href="#" class="lgp-link">
    <i class="fa-solid fa-arrow-right"></i>
    Continue
</a>
```

**Hover Behavior:**
- Color changes from brand (#3B82F6) to brand-hover (#2563EB)
- Underline variant shows underline on hover
- Fast 150ms transition

---

### Empty States

**Empty State Structure:**
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

**When to Use:**
- No search results
- Empty ticket list
- No companies assigned
- No training videos uploaded
- Empty filters/selections

---

### Loading Spinner

**Spinner Markup:**
```html
<!-- Default spinner (20px) -->
<span class="lgp-spinner"></span>

<!-- Large spinner (40px) -->
<span class="lgp-spinner lgp-spinner-lg"></span>

<!-- Inline with text -->
<button class="lgp-btn lgp-btn-primary" disabled>
    <span class="lgp-spinner"></span>
    Loading...
</button>
```

---

### Avatar

**Avatar Markup:**
```html
<!-- Initials avatar -->
<div class="lgp-avatar">JD</div>

<!-- Image avatar -->
<div class="lgp-avatar">
    <img src="user-photo.jpg" alt="John Doe">
</div>

<!-- With size variants -->
<div class="lgp-avatar lgp-avatar-sm">JD</div>  <!-- 32px -->
<div class="lgp-avatar">JD</div>                <!-- 40px -->
<div class="lgp-avatar lgp-avatar-lg">JD</div>  <!-- 48px -->
```

**In Context:**
```html
<div class="lgp-flex lgp-items-center lgp-gap-2">
    <div class="lgp-avatar">JD</div>
    <div>
        <div class="lgp-font-semibold">John Doe</div>
        <div class="lgp-text-sm lgp-text-muted">Partner Account</div>
    </div>
</div>
```

---

### Dividers

**Horizontal Divider:**
```html
<div class="lgp-divider"></div>
```

**Vertical Divider:**
```html
<div class="lgp-flex lgp-items-center">
    <span>Item 1</span>
    <div class="lgp-divider-vertical"></div>
    <span>Item 2</span>
</div>
```

---

### Tooltips (CSS-only)

**Tooltip Markup:**
```html
<span class="lgp-tooltip" data-tooltip="This is helpful info">
    <i class="fa-solid fa-circle-info"></i>
</span>

<button class="lgp-btn lgp-btn-icon lgp-tooltip" data-tooltip="Edit Company">
    <i class="fa-solid fa-pen"></i>
</button>
```

**Tooltip Appears:**
- On hover (desktop)
- Above the element
- Dark background with white text
- Fade in/out transition

---

## Color Palette

### Brand Colors
```css
--lgp-color-brand: #3B82F6           /* Primary blue */
--lgp-color-brand-hover: #2563EB     /* Darker on hover */
--lgp-color-brand-light: #DBEAFE     /* Light blue background */
```

### Status Colors (WCAG AA Compliant)
```css
--lgp-color-success: #16A34A         /* Green */
--lgp-color-success-light: #DCFCE7   /* Light green bg */

--lgp-color-warning: #D97706         /* Orange */
--lgp-color-warning-light: #FEF3C7   /* Light yellow bg */

--lgp-color-danger: #DC2626          /* Red */
--lgp-color-danger-light: #FEE2E2    /* Light red bg */

--lgp-color-info: #2563EB            /* Blue */
--lgp-color-info-light: #DBEAFE      /* Light blue bg */
```

### Neutral Colors
```css
--lgp-color-background: #FFFFFF      /* Page background */
--lgp-color-surface: #F7F8FA         /* Card/panel surface */
--lgp-color-surface-raised: #FFFFFF  /* Raised card */
--lgp-color-border: #E5E7EB          /* Borders */
--lgp-color-border-hover: #D1D5DB    /* Border on hover */
```

### Text Colors
```css
--lgp-color-text-primary: #111827    /* Main text */
--lgp-color-text-secondary: #6B7280  /* Secondary text */
--lgp-color-text-tertiary: #9CA3AF   /* Tertiary/disabled */
--lgp-color-text-inverse: #FFFFFF    /* On dark backgrounds */
```

---

## Spacing Scale (8px Grid)

```css
--lgp-space-0: 0
--lgp-space-1: 0.25rem   /* 4px */
--lgp-space-2: 0.5rem    /* 8px */
--lgp-space-3: 0.75rem   /* 12px */
--lgp-space-4: 1rem      /* 16px */
--lgp-space-5: 1.25rem   /* 20px */
--lgp-space-6: 1.5rem    /* 24px */
--lgp-space-8: 2rem      /* 32px */
--lgp-space-10: 2.5rem   /* 40px */
--lgp-space-12: 3rem     /* 48px */
--lgp-space-16: 4rem     /* 64px */
```

**Usage:**
```html
<div class="lgp-my-4">16px vertical margin</div>
<div class="lgp-px-6">24px horizontal padding</div>
<div class="lgp-gap-4">16px gap in flexbox</div>
```

---

## Typography Scale

```css
--lgp-font-size-xs: 0.75rem     /* 12px - Small labels */
--lgp-font-size-sm: 0.875rem    /* 14px - Body text */
--lgp-font-size-base: 1rem      /* 16px - Base */
--lgp-font-size-lg: 1.125rem    /* 18px - Card titles */
--lgp-font-size-xl: 1.25rem     /* 20px - Section headers */
--lgp-font-size-2xl: 1.5rem     /* 24px - Page titles */
```

**Font Weights:**
```css
--lgp-font-weight-regular: 400
--lgp-font-weight-medium: 500
--lgp-font-weight-semibold: 600
--lgp-font-weight-bold: 700
```

**Utility Classes:**
```html
<p class="lgp-text-xs">Extra small text</p>
<p class="lgp-text-sm">Small text</p>
<p class="lgp-text-lg">Large text</p>
<p class="lgp-font-semibold">Semibold weight</p>
<p class="lgp-text-muted">Secondary color</p>
```

---

## Layout Utilities

**Flexbox:**
```html
<div class="lgp-flex lgp-items-center lgp-justify-between lgp-gap-4">
    <span>Left content</span>
    <span>Right content</span>
</div>

<div class="lgp-flex lgp-flex-col lgp-gap-2">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

**Spacing:**
```html
<div class="lgp-mt-4">Margin top</div>
<div class="lgp-mb-4">Margin bottom</div>
<div class="lgp-mx-4">Horizontal margin</div>
<div class="lgp-my-4">Vertical margin</div>

<div class="lgp-pt-4">Padding top</div>
<div class="lgp-pb-4">Padding bottom</div>
<div class="lgp-px-4">Horizontal padding</div>
<div class="lgp-py-4">Vertical padding</div>
```

**Text Alignment:**
```html
<p class="lgp-text-center">Centered</p>
<p class="lgp-text-right">Right aligned</p>
```

---

## Responsive Breakpoints

Cards and grids are automatically responsive:
- **Desktop (>768px):** Auto-fill grid with 320px minimum
- **Tablet/Mobile (≤768px):** Single column layout

Custom breakpoints in templates:
```css
@media (max-width: 768px) {
    /* Mobile styles */
}
```

---

## Dark Mode (Automatic)

Dark mode tokens are already defined and activate automatically via `prefers-color-scheme: dark`. All components adjust colors automatically.

**Dark Mode Colors:**
- Background: #111827
- Surface: #1F2937
- Raised surface: #374151
- Text: #F9FAFB
- Borders: #4B5563

---

## Best Practices

### ✅ Do:
- Use card grid for unit/ticket/company lists
- Add icons to buttons for clarity
- Use status badges for state representation
- Provide hover states on all interactive elements
- Maintain 8px spacing grid consistency
- Use semantic color coding (success=green, danger=red)
- Add empty states for no-data scenarios
- Use loading spinners for async operations

### ❌ Don't:
- Mix icon libraries (stick to FontAwesome)
- Use gradients or heavy textures
- Create custom colors outside the palette
- Ignore whitespace (avoid cramped layouts)
- Forget hover states on clickable elements
- Use multiple font families
- Override z-index without using scale

---

## Example: Company List Card Grid

```html
<div class="lgp-card-grid">
    <!-- Company Card 1 -->
    <div class="lgp-card lgp-card-clickable" onclick="window.location='/portal/company/123'">
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
            <a href="/portal/company/123" class="lgp-link">
                View Details <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <!-- More cards... -->
</div>
```

---

## Example: Ticket Table with Actions

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
                <td class="lgp-table-numeric">2 hours ago</td>
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

---

## Integration Checklist

When updating portal templates:

- [ ] Replace old card markup with `.lgp-card` structure
- [ ] Add icon library classes for FontAwesome icons
- [ ] Use `.lgp-card-grid` for card layouts
- [ ] Update table markup with `.lgp-table-wrapper` and `.lgp-table`
- [ ] Replace inline status text with `.lgp-badge` components
- [ ] Add hover-friendly `.lgp-table-actions` for row actions
- [ ] Use utility classes (`.lgp-flex`, `.lgp-gap-4`, etc.) for spacing
- [ ] Add empty states where applicable
- [ ] Include loading spinners for async operations
- [ ] Test hover states on all interactive elements
- [ ] Verify responsive behavior on mobile/tablet

---

**For More Details:**  
See [design-tokens.css](loungenie-portal/assets/css/design-tokens.css) for the complete CSS implementation.

**Icon Reference:**  
FontAwesome 6 Free: https://fontawesome.com/icons

---

**Ready to use!** All components are now available in the design tokens CSS and automatically loaded in the portal.
