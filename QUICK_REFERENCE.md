# Quick Reference - LounGenie Portal Development

## 🎨 Using Brand Colors in CSS

### ✅ DO: Use Token Variables
```css
.my-component {
    color: var(--lg-primary);           /* Teal */
    background-color: var(--lg-secondary);  /* Sky */
    border-color: var(--lg-structure);  /* Navy */
}
```

### ❌ DON'T: Use Raw Hex
```css
.my-component {
    color: #1CAAB6;  /* Bad - hardcoded, breaks with theme override */
}
```

---

## 🎯 Brand Palette (4 Colors)

| Name | Hex | Variable | Usage |
|------|-----|----------|-------|
| Teal | #1CAAB6 | `--lg-primary` | Buttons, links, partner portal |
| Sky | #67BED9 | `--lg-secondary` | Support portal, hover states |
| Sea | #4DA88F | `--lg-success` | Success badges, completed status |
| Navy | #1A237E | `--lg-structure` | Text, navigation, headings |

---

## 📂 File Locations

| Purpose | File | Location |
|---------|------|----------|
| Brand Colors | design-tokens.css | `/assets/css/` |
| UI Components | portal-components.css | `/assets/css/` |
| Main Layout | portal.css | `/assets/css/` |
| Portal HTML | portal-shell.php | `/templates/` |
| Enqueue Assets | class-lgp-assets.php | `/includes/` |

---

## 🔧 Adding a New Component

### 1. Create CSS in portal-components.css
```css
.lgp-my-component {
    color: var(--lg-text-primary);
    background-color: var(--lg-bg-primary);
    border: 1px solid var(--lg-border-light);
}

.lgp-my-component:hover {
    border-color: var(--lg-primary);
}
```

### 2. Use in Template
```html
<div class="lgp-my-component">
    My Component Content
</div>
```

### 3. No CSS imports needed
- Assets are enqueued automatically by router
- Token variables available everywhere

---

## 🚦 Portal Access Control

### Check if User is Support
```php
if (LGP_Auth::is_support()) {
    // Show support-only features
}
```

### Check if User is Partner
```php
if (LGP_Auth::is_partner()) {
    // Show partner-only features
}
```

### Require Authentication
```php
if (!is_user_logged_in() || !LGP_Auth::is_support()) {
    wp_redirect(home_url('/portal/login'));
    exit;
}
```

---

## 🎯 Common Patterns

### Button
```html
<button class="lgp-btn lgp-btn-primary">Click Me</button>
<button class="lgp-btn lgp-btn-secondary">Secondary</button>
<button class="lgp-btn lgp-btn-outline">Outline</button>
```

### Card
```html
<div class="lgp-card">
    <h3 class="lgp-card-title">Card Title</h3>
    <p>Card content here</p>
</div>
```

### Form Input
```html
<input type="text" class="lgp-input" placeholder="Enter text">
<textarea class="lgp-input" placeholder="Enter message"></textarea>
```

### Navigation Link
```html
<a href="/portal/page" class="lgp-nav-link">Page Name</a>
```

### Status Badge
```html
<span class="lgp-badge-success">Completed</span>
<span class="lgp-badge-primary">Active</span>
<span class="lgp-badge-warning">Pending</span>
```

---

## 🐛 Debugging

### Check if token is loaded
```javascript
// In browser console
getComputedStyle(document.documentElement)
  .getPropertyValue('--lg-primary')
// Should return: " #1CAAB6"
```

### Verify !important applied
```javascript
getComputedStyle(document.documentElement)
  .getPropertyPriority('--lg-primary')
// Should return: "important"
```

### Check component uses token
```javascript
let btn = document.querySelector('.lgp-btn');
let bg = getComputedStyle(btn).backgroundColor;
// Should be #1CAAB6 (or rgb equivalent)
```

---

## 📋 CSS Class Naming Convention

All portal CSS classes start with `lgp-` prefix:

```
.lgp-button         ← Component
.lgp-button-primary ← Variant
.lgp-card           ← Component
.lgp-card-title     ← Element
.lgp-nav-link       ← Component
.lgp-nav-link.active ← State
```

---

## 🔗 Useful Links

- **Brand Colors:** `loungenie-portal/assets/css/design-tokens.css`
- **Portal Shell:** `loungenie-portal/templates/portal-shell.php`
- **Enqueue Assets:** `loungenie-portal/includes/class-lgp-assets.php`
- **Auth Helper:** `loungenie-portal/includes/class-lgp-auth.php`
- **Full Guide:** `DESIGN_TOKENS_GUIDE.md`

---

## ⚡ Common Tasks

### Add new color to palette
1. Open `design-tokens.css`
2. Add to `:root` with `!important`
3. Use in CSS: `color: var(--lg-new-color)`

### Hide element from partners
1. Check role: `if (LGP_Auth::is_support())`
2. Show content conditionally in template

### Update a brand color
1. Open `design-tokens.css`
2. Change one hex value (e.g., `#1CAAB6` → `#00B4A6`)
3. All CSS automatically uses new color!

### Add hover effect
1. Create CSS rule: `.lgp-element:hover`
2. Use token: `color: var(--lg-secondary)`
3. No hardcoded hex needed

---

## ✅ Checklist for New Features

- [ ] All colors from 4-token palette
- [ ] No inline styles (use CSS classes)
- [ ] No hardcoded hex colors
- [ ] CSS classes start with `lgp-`
- [ ] Role checks if needed (`is_support()`, `is_partner()`)
- [ ] All output escaped (`esc_html()`, `esc_url()`)
- [ ] Responsive on mobile
- [ ] Works on all themes
- [ ] No console errors

---

**Quick Reference v1.0**  
Generated: January 6, 2025  
For detailed info, see `DESIGN_TOKENS_GUIDE.md`
