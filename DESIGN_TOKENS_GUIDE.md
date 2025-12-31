# Design Token System - Developer Guide

## Overview

The LounGenie Portal uses a **unified 4-color design token system** to ensure brand consistency and protect against WordPress theme override.

**Goal:** One set of colors, used everywhere, enforced with `!important`.

---

## Brand Palette

| Token | Hex Value | Role | Usage |
|-------|-----------|------|-------|
| `--lg-primary` | `#1CAAB6` | Teal | Partner portal, buttons, active states, links |
| `--lg-secondary` | `#67BED9` | Sky | Support portal, secondary UI, hover states |
| `--lg-success` | `#4DA88F` | Sea | Success messages, completed badges, confirmed status |
| `--lg-structure` | `#1A237E` | Navy | Primary text, navigation, headings, borders |

---

## Token Definition

**File:** `loungenie-portal/assets/css/design-tokens.css`

### Root Level (Global Scope)
```css
:root {
    --lg-primary: #1CAAB6 !important;
    --lg-secondary: #67BED9 !important;
    --lg-success: #4DA88F !important;
    --lg-structure: #1A237E !important;
}
```

### Portal Container Scope (Enforcement)
```css
html, body, .lgp-portal, .lgp-portal-body {
    --lg-primary: #1CAAB6 !important;
    --lg-secondary: #67BED9 !important;
    --lg-success: #4DA88F !important;
    --lg-structure: #1A237E !important;
}
```

**Why `!important`?**
- Prevents WordPress theme CSS from overriding brand colors
- Ensures consistency across any active theme
- Guarantees brand identity on shared hosting

---

## Derived Variables

The 4 core tokens generate all other color needs via CSS variables:

### Text Colors
```css
--lg-text-primary: var(--lg-structure);      /* Navy - main text */
--lg-text-secondary: rgba(26, 35, 126, 0.7); /* Navy 70% opacity - secondary text */
--lg-text-inverse: #FFFFFF;                  /* White - text on dark backgrounds */
--lg-text-muted: rgba(26, 35, 126, 0.5);    /* Navy 50% opacity - placeholder text */
```

### Background Colors
```css
--lg-bg-primary: #FFFFFF;                    /* White - main content */
--lg-bg-secondary: #F5F7FA;                  /* Light gray - cards, sections */
--lg-bg-overlay: rgba(0, 0, 0, 0.5);        /* Black 50% - modals, overlays */
```

### Border Colors
```css
--lg-border-primary: var(--lg-structure);    /* Navy borders */
--lg-border-light: #E8E8E8;                  /* Light borders */
--lg-border-focus: var(--lg-primary);        /* Teal focus rings */
```

### State Colors
```css
--lg-state-hover: var(--lg-secondary);       /* Sky - hover effects */
--lg-state-focus: var(--lg-primary);         /* Teal - focus indicators */
--lg-state-active: var(--lg-primary);        /* Teal - active buttons/links */
--lg-state-disabled: #CCCCCC;                /* Gray - disabled state */
--lg-state-error: #D32F2F;                   /* Red - errors (fixed) */
--lg-state-warning: #F57C00;                 /* Orange - warnings (fixed) */
```

---

## Usage in CSS

### ✅ DO: Use Token Variables

```css
/* Buttons */
.lgp-button {
    background-color: var(--lg-primary);
    color: var(--lg-text-inverse);
    border: 1px solid var(--lg-border-primary);
}

.lgp-button:hover {
    background-color: var(--lg-secondary);
}

/* Links */
.lgp-link {
    color: var(--lg-primary);
    text-decoration: none;
}

.lgp-link:hover {
    color: var(--lg-secondary);
}

/* Form Fields */
.lgp-input {
    border: 1px solid var(--lg-border-light);
    color: var(--lg-text-primary);
}

.lgp-input:focus {
    border-color: var(--lg-border-focus);
    outline: 2px solid var(--lg-state-focus);
}

/* Headers */
.lgp-header {
    background-color: var(--lg-primary);
    color: var(--lg-text-inverse);
    border-bottom: 2px solid var(--lg-structure);
}

/* Status Badges */
.lgp-badge-success {
    background-color: var(--lg-success);
    color: var(--lg-text-inverse);
}

.lgp-badge-primary {
    background-color: var(--lg-primary);
    color: var(--lg-text-inverse);
}
```

### ❌ DON'T: Use Raw Hex Colors

```css
/* ❌ BAD - Hardcoded hex */
.lgp-button {
    background-color: #1CAAB6;  /* If theme overrides this, portal breaks */
}

/* ❌ BAD - Different hex than token */
.lgp-link {
    color: #00A0B0;  /* Close to teal but not exact - inconsistent */
}

/* ❌ BAD - No token reference */
.lgp-header {
    background-color: #1ca9b7;  /* Typo in hex value */
}
```

---

## Token Hierarchy

### CSS Specificity & Override Prevention

```
1. Design Token (@root) ← HIGHEST PRIORITY
   └─ --lg-primary: #1CAAB6 !important

2. Derived Variables (design-tokens.css)
   └─ --lg-text-primary: var(--lg-structure)

3. Component CSS (portal-components.css)
   └─ .lgp-button { color: var(--lg-text-primary); }

4. Theme CSS (DEQUEUED, never loads)
   └─ .button { color: theme-accent-color; } ← REMOVED
```

**Result:** Portal colors always use brand tokens, never theme colors.

---

## Maintenance & Updates

### Adding a New Color Token

If the design requires a new color (e.g., warning color):

**Step 1:** Add to `design-tokens.css`:
```css
:root {
    --lg-warning: #FF9800 !important; /* Orange */
}

/* Enforcement scope */
html, body, .lgp-portal {
    --lg-warning: #FF9800 !important;
}
```

**Step 2:** Add derived variables (if needed):
```css
--lg-bg-warning: rgba(255, 152, 0, 0.1);  /* Light orange background */
--lg-border-warning: var(--lg-warning);    /* Orange border */
```

**Step 3:** Use in CSS:
```css
.lgp-warning-message {
    background-color: var(--lg-bg-warning);
    border-left: 4px solid var(--lg-warning);
    color: var(--lg-text-primary);
}
```

**Step 4:** Update this document

### Changing a Color (Brand Update)

To update the teal color (e.g., `#1CAAB6` → `#00B4A6`):

**Step 1:** Update `design-tokens.css`:
```css
:root {
    --lg-primary: #00B4A6 !important; /* NEW VALUE */
}
```

**Step 2:** Update enforcement scope:
```css
html, body, .lgp-portal {
    --lg-primary: #00B4A6 !important; /* NEW VALUE */
}
```

**Step 3:** No CSS file changes needed! All references use `var(--lg-primary)`, so they update automatically.

**Step 4:** Test:
- Buttons change color ✅
- Links change color ✅
- Headers change color ✅
- No console errors ✅

---

## Backward Compatibility

### Legacy Variables

For old code that references `--lgp-*` variables:

```css
/* Legacy aliases (for backward compatibility) */
--lgp-primary-color: var(--lg-primary);
--lgp-secondary-color: var(--lg-secondary);
--lgp-success-color: var(--lg-success);
--lgp-text-color: var(--lg-structure);
```

**Usage in old components:**
```css
.old-component {
    color: var(--lgp-text-color); /* Still works! Maps to --lg-structure */
}
```

---

## Browser Support

### CSS Variables Support
- Chrome 49+ ✅
- Firefox 31+ ✅
- Safari 9.1+ ✅
- Edge 15+ ✅
- **Internet Explorer 11** ❌ (Not supported - fallback to fixed color)

### Fallback Strategy

For IE11 users (if required):

```css
.lgp-button {
    background-color: #1CAAB6; /* Fallback (static) */
    background-color: var(--lg-primary); /* Modern browsers (dynamic) */
}
```

---

## DevTools Debugging

### Inspect Token Value

**Chrome DevTools:**
1. Right-click on element
2. Select "Inspect"
3. In "Styles" panel, find `color: var(--lg-primary)`
4. Hover over variable name
5. Tooltip shows computed value: `#1CAAB6` ✅

### Verify Token is Loaded

**Console:**
```javascript
// Check if token is defined
getComputedStyle(document.documentElement)
  .getPropertyValue('--lg-primary')
// Output: " #1CAAB6" ✅

// Verify !important is applied
getComputedStyle(document.documentElement)
  .getPropertyPriority('--lg-primary')
// Output: "important" ✅
```

### Check for Theme Override

**Console - Find conflicting selectors:**
```javascript
// If theme tries to override portal button color
let btn = document.querySelector('.lgp-button');
let computed = getComputedStyle(btn);
console.log(computed.backgroundColor); // Should be #1CAAB6

// If it's NOT #1CAAB6, theme CSS is overriding
// Solution: Check dequeue_theme_styles() is working
```

---

## Common Patterns

### Button Variants

```css
/* Primary Button */
.lgp-btn-primary {
    background-color: var(--lg-primary);
    color: var(--lg-text-inverse);
}

/* Secondary Button */
.lgp-btn-secondary {
    background-color: var(--lg-secondary);
    color: var(--lg-text-inverse);
}

/* Outline Button */
.lgp-btn-outline {
    background-color: transparent;
    border: 2px solid var(--lg-primary);
    color: var(--lg-primary);
}

.lgp-btn-outline:hover {
    background-color: var(--lg-primary);
    color: var(--lg-text-inverse);
}
```

### Card Styling

```css
.lgp-card {
    background-color: var(--lg-bg-primary);
    border: 1px solid var(--lg-border-light);
    border-top: 4px solid var(--lg-primary);
}

.lgp-card:hover {
    border-top-color: var(--lg-secondary);
}
```

### Navigation

```css
.lgp-nav-link {
    color: var(--lg-text-primary);
    border-left: 3px solid transparent;
}

.lgp-nav-link:hover,
.lgp-nav-link.active {
    color: var(--lg-primary);
    border-left-color: var(--lg-primary);
    background-color: var(--lg-bg-secondary);
}
```

### Form Validation

```css
.lgp-input {
    border: 1px solid var(--lg-border-light);
}

.lgp-input:focus {
    border-color: var(--lg-state-focus);
    box-shadow: 0 0 0 3px var(--lg-state-focus);
    opacity: 0.1;
}

.lgp-input.error {
    border-color: var(--lg-state-error);
}

.lgp-input.success {
    border-color: var(--lg-state-success);
}
```

---

## FAQ

### Q: Can I use a different color in my custom CSS?

**A:** No. All portal colors must come from the 4 brand tokens. If you need a new color:
1. Request design approval
2. Add to `design-tokens.css`
3. Document in this guide

This ensures brand consistency and maintainability.

---

### Q: What if the WordPress theme has the same token names?

**A:** The `!important` flag in `:root` makes our tokens take precedence. Your tokens will always win.

---

### Q: Can I use RGB colors instead of hex?

**A:** Yes, but tokens must be in `design-tokens.css`. Don't add RGB colors in component CSS.

**Example:**
```css
/* ✅ In design-tokens.css */
--lg-primary-rgb: 28, 170, 182; /* RGB version of #1CAAB6 */

/* ✅ In component CSS */
.lgp-button {
    background-color: rgb(var(--lg-primary-rgb));
}

/* For transparency */
.lgp-button-transparent {
    background-color: rgba(var(--lg-primary-rgb), 0.1);
}
```

---

### Q: How do I add a dark mode?

**A:** Add dark mode tokens:

```css
:root {
    --lg-primary: #1CAAB6;  /* Light mode */
    --lg-text-primary: #1A237E;
    /* ... */
}

/* Dark Mode */
:root.dark-mode {
    --lg-primary: #4DD9E8;  /* Lighter teal for dark bg */
    --lg-text-primary: #FFFFFF;
    /* ... */
}
```

Then toggle class on `<html>` element. All CSS automatically updates!

---

**Document Version:** 1.0  
**Last Updated:** January 6, 2025  
**Related Files:** 
- `loungenie-portal/assets/css/design-tokens.css`
- `loungenie-portal/assets/css/portal-components.css`
- `loungenie-portal/assets/css/design-system-refactored.css`
