# 🎨 PoolSafe Portal Design System & Styling Guide (v3.3.0)

**Date:** December 10, 2025  
**Version:** v3.3.0  
**Status:** ✅ Theme-Integrated & Production Ready

---

## Design Philosophy

The PoolSafe Portal uses a **modern, clean design system** that:
- ✅ Inherits WordPress theme colors dynamically
- ✅ Provides consistent component styling
- ✅ Ensures full responsive design (mobile-first)
- ✅ Maintains WCAG 2.1 AA accessibility
- ✅ Uses semantic HTML with proper ARIA labels
- ✅ Follows modern CSS best practices

---

## Color Palette

### WordPress Theme Integration
Colors automatically inherit from your WordPress theme's color presets:

```css
--psp-color-primary:    var(--wp--preset--color--primary, #0EA5E9)
--psp-color-secondary:  var(--wp--preset--color--secondary, #14B8A6)
--psp-color-text:       var(--wp--preset--color--foreground, #1E293B)
--psp-color-text-light: var(--wp--preset--color--tertiary, #64748B)
--psp-color-bg:         var(--wp--preset--color--background, #FFFFFF)
--psp-color-surface:    var(--wp--preset--color--base, #F8FAFC)
--psp-color-border:     var(--wp--preset--color--contrast, #E2E8F0)
```

### Default Colors (Fallback)
- **Primary:** `#0EA5E9` (Sky Blue)
- **Secondary:** `#14B8A6` (Teal)
- **Text:** `#1E293B` (Dark Slate)
- **Text Light:** `#64748B` (Medium Gray)
- **Background:** `#FFFFFF` (White)
- **Surface:** `#F8FAFC` (Light Gray)
- **Border:** `#E2E8F0` (Light Border)

### Semantic Colors
- **Success:** `#10b981` (Green)
- **Danger:** `#ef4444` (Red)
- **Warning:** `#f59e0b` (Amber)
- **Info:** `#0ea5e9` (Blue)

---

## Typography

### Font Family
Uses system font stack for optimal performance:
```css
-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif
```

### Font Sizes
All text sizes use `clamp()` for responsive scaling without media queries:

| Element | Mobile | Desktop | CSS |
|---------|--------|---------|-----|
| Header Title | 1.35rem | 2rem | `clamp(1.35rem, 3.6vw, 2rem)` |
| Section Title | 1.125rem | 1.5rem | `clamp(1.125rem, 3vw, 1.5rem)` |
| Button Text | 0.875rem | 1rem | `clamp(0.875rem, 2vw, 1rem)` |
| Body Text | 0.875rem | 1rem | `clamp(0.875rem, 2.5vw, 1rem)` |
| Small Text | 0.75rem | 0.9375rem | `clamp(0.75rem, 2vw, 0.9375rem)` |

### Font Weights
- **Regular:** 400
- **Medium:** 500
- **Semibold:** 600
- **Bold:** 700
- **Extra Bold:** 800

---

## Spacing System

Responsive spacing that adjusts based on viewport:

```css
/* Mobile (320px - 639px) */
--psp-spacing-xs:  0.5rem
--psp-spacing-sm:  0.75rem
--psp-spacing-md:  1rem
--psp-spacing-lg:  1.5rem
--psp-spacing-xl:  2rem

/* Tablet (640px - 1024px) */
--psp-spacing-xs:  0.625rem
--psp-spacing-sm:  0.875rem
--psp-spacing-md:  1.25rem
--psp-spacing-lg:  1.75rem
--psp-spacing-xl:  2.5rem

/* Desktop (1024px+) */
--psp-spacing-xs:  0.75rem
--psp-spacing-sm:  1rem
--psp-spacing-md:  1.5rem
--psp-spacing-lg:  2rem
--psp-spacing-xl:  3rem
```

---

## Border Radius

Consistent rounded corners throughout:

```css
--psp-radius-sm:    0.375rem  /* Small elements */
--psp-radius-md:    0.5rem    /* Input fields, small cards */
--psp-radius-lg:    0.75rem   /* Cards, modals, main components */
--psp-radius-pill:  1.5rem    /* Buttons, badges */
```

---

## Shadows

Layered shadow system for depth:

```css
--psp-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05)
--psp-shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07)
--psp-shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1)
```

**Usage:**
- `shadow-sm`: Subtle borders, minimal depth
- `shadow-md`: Cards, panels, normal elements
- `shadow-lg`: Hero sections, modals, emphasis

---

## Components

### Buttons

#### Primary Button
```html
<button class="psp-button psp-button-primary">
  Primary Action
</button>
```
**Usage:** Main actions, primary calls-to-action

**States:**
- Normal: Blue background with white text
- Hover: Secondary color, lifted effect
- Focus: Outline for accessibility
- Active: Pressed effect
- Disabled: Reduced opacity

#### Secondary Button
```html
<button class="psp-button psp-button-secondary">
  Secondary Action
</button>
```
**Usage:** Alternative actions, secondary CTAs

#### Outline Button
```html
<button class="psp-button psp-button-outline">
  Outline Action
</button>
```
**Usage:** Tertiary actions, less emphasis

#### Ghost Button
```html
<button class="psp-button psp-button-ghost">
  Text Link Button
</button>
```
**Usage:** Links within content, minimal styling

#### Danger Button
```html
<button class="psp-button psp-button-danger">
  Delete
</button>
```
**Usage:** Destructive actions, confirmations

#### Success Button
```html
<button class="psp-button psp-button-success">
  Save
</button>
```
**Usage:** Positive confirmations, success states

#### Button Sizes
```html
<button class="psp-button psp-button-primary psp-button-sm">Small</button>
<button class="psp-button psp-button-primary">Default</button>
<button class="psp-button psp-button-primary psp-button-lg">Large</button>
```

### Alerts

#### Success Alert
```html
<div class="psp-alert psp-alert-success">
  ✓ Operation completed successfully
</div>
```

#### Error Alert
```html
<div class="psp-alert psp-alert-danger">
  ❌ An error occurred. Please try again.
</div>
```

#### Info Alert
```html
<div class="psp-alert psp-alert-info">
  ℹ️ Here's some helpful information
</div>
```

#### Warning Alert
```html
<div class="psp-alert psp-alert-warning">
  ⚠️ Please review before proceeding
</div>
```

### Cards & Stats

#### Stat Card
```html
<div class="psp-stat-card">
  <div class="psp-stat-icon">🎫</div>
  <div class="psp-stat-label">Open Tickets</div>
  <div class="psp-stat-value">24</div>
</div>
```
**Features:**
- Hover lift effect
- Top border accent on hover
- Icon, label, and value layout
- Responsive sizing

### Loading & Spinner

#### Loading State
```html
<div class="psp-loading">
  Loading your data...
</div>
```

Built-in spinner animation with rotating indicator.

### Tabs

#### Tab Navigation
```html
<nav class="psp-tab-nav">
  <ul class="psp-tab-list">
    <li class="psp-tab-item">
      <button class="psp-tab-button active" data-tab="dashboard">
        Dashboard
      </button>
    </li>
    <li class="psp-tab-item">
      <button class="psp-tab-button" data-tab="tickets">
        Tickets
      </button>
    </li>
  </ul>
</nav>
```

**Features:**
- Keyboard navigation (Tab, Arrow keys)
- ARIA roles and attributes
- Active state indicator (bottom border)
- Hover effects
- Mobile-friendly scroll support

---

## Layout Structure

### Header
- Gradient background (primary to secondary)
- Logo badge with glass effect
- Title and subtitle
- User info (mobile hidden)
- Logout button
- Responsive layout

### Tab Navigation
- Horizontal scrollable on mobile
- Sticky appearance
- Active indicator
- Category text display

### Content Area
- Centered max-width (1400px)
- Responsive padding
- Dynamic content loading
- Fade-in animations

### Welcome Banner
- Gradient background
- Large heading
- Subtle background effects
- Clear call-to-action support

### Stats Grid
- 1 column on mobile
- 2 columns on tablet
- 4 columns on desktop
- Responsive gaps
- Hover animations

---

## Responsive Breakpoints

### Mobile First Approach
```css
/* Mobile: 320px - 639px (default) */
/* Tablet: 640px - 1024px */
@media (min-width: 640px) { ... }

/* Desktop: 1024px - 1439px */
@media (min-width: 1024px) { ... }

/* Large Desktop: 1440px+ */
@media (min-width: 1440px) { ... }

/* Print */
@media print { ... }
```

### Mobile Adjustments
- Single column layouts
- Stacked buttons
- Full-width inputs
- Hidden meta info
- Centered headers

### Tablet Optimizations
- 2-column grids
- Side-by-side layouts
- Better spacing

### Desktop Experience
- 4-column grids
- Multi-column layouts
- Full navigation visibility

---

## Animations & Transitions

### Fade In
```css
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
```
**Duration:** 0.4s  
**Easing:** cubic-bezier(0.4, 0, 0.2, 1)

### Spin (Loading)
```css
@keyframes spin {
    to { transform: rotate(360deg); }
}
```
**Duration:** 0.8s  
**Timing:** linear infinite

### Button Hover
- Transform: `translateY(-2px)` lift effect
- Duration: 0.25s
- Easing: cubic-bezier(0.4, 0, 0.2, 1)

---

## Accessibility Features

### Color Contrast
All text meets WCAG 2.1 AA standards:
- Normal text: 4.5:1 minimum
- Large text: 3:1 minimum

### Keyboard Navigation
- Tab through all interactive elements
- Focus indicators on all buttons
- Skip links support
- ARIA labels on dynamic content

### Screen Reader Support
- Semantic HTML (`<button>`, `<nav>`, `<main>`)
- ARIA roles: `role="tab"`, `role="tabpanel"`, `role="navigation"`
- ARIA labels: `aria-selected`, `aria-controls`, `aria-label`
- Alt text on icons

### Motion Preferences
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## CSS Custom Properties (Variables)

All styling uses CSS variables for easy theme customization:

```css
:root {
    /* Colors */
    --psp-color-primary
    --psp-color-secondary
    --psp-color-text
    --psp-color-text-light
    --psp-color-bg
    --psp-color-surface
    --psp-color-border
    
    /* Effects */
    --psp-gradient-primary
    --psp-shadow-sm
    --psp-shadow-md
    --psp-shadow-lg
    
    /* Sizing */
    --psp-radius-sm
    --psp-radius-md
    --psp-radius-lg
    --psp-radius-pill
    
    /* Spacing */
    --psp-spacing-xs
    --psp-spacing-sm
    --psp-spacing-md
    --psp-spacing-lg
    --psp-spacing-xl
}
```

---

## Customization

### Changing Theme Colors

To override theme colors, add this to your custom CSS:

```css
:root {
    --psp-color-primary: #your-color;
    --psp-color-secondary: #your-color;
    /* etc... */
}
```

Or modify in WordPress Theme Customizer if supported.

### Adjusting Spacing

Modify responsive spacing values:

```css
@media (min-width: 640px) {
    :root {
        --psp-spacing-md: 1.25rem;
        --psp-spacing-lg: 1.75rem;
    }
}
```

---

## Best Practices

### Do's ✅
- Use CSS variables for all values
- Use `clamp()` for responsive sizing
- Follow semantic HTML structure
- Test with keyboard navigation
- Use proper ARIA attributes
- Test on mobile, tablet, desktop

### Don'ts ❌
- Don't use inline styles
- Don't hardcode color values
- Don't skip ARIA labels
- Don't break keyboard navigation
- Don't assume touch devices
- Don't forget mobile users

---

## Testing Checklist

- [ ] Colors visible on light and dark backgrounds
- [ ] All interactive elements keyboard accessible
- [ ] Screen reader announces all content
- [ ] Responsive on 320px, 640px, 1024px, 1440px
- [ ] Touch targets at least 44x44px
- [ ] Focus indicators visible on all buttons
- [ ] Animations respect prefers-reduced-motion
- [ ] Forms clearly labeled
- [ ] Error messages descriptive

---

## Browser Support

| Browser | Min Version | Status |
|---------|-------------|--------|
| Chrome/Edge | 90+ | ✅ Full support |
| Firefox | 88+ | ✅ Full support |
| Safari | 14+ | ✅ Full support |
| Mobile Safari | 14+ | ✅ Full support |
| Chrome Android | 90+ | ✅ Full support |

---

**Design System Status:** ✅ Complete & Production Ready

**Version:** 3.3.0  
**Last Updated:** December 10, 2025
