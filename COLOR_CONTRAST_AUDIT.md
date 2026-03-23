# Color & Contrast Audit Report - Loungenie Staging

## Critical Issue Found: Header Menu Text Visibility

### Problem Location: Main Site Header (https://loungenie.com/staging/)

**Element:** `.wp-block-navigation-item__content` (navigation links in sticky header)

**Current CSS:**
```css
.wp-site-blocks > header.wp-block-template-part {
  background: rgba(4,20,40,.9);        /* Dark navy - ~90% opacity */
  backdrop-filter: blur(13px);
}

.wp-site-blocks > header .wp-block-navigation-item__content {
  color: var(--lg-ink) !important;     /* #0b1726 - ALSO dark navy */
  font-size: 14px !important;
  font-weight: 700 !important;
}
```

**Color Values:**
- Header background: `rgba(4,20,40,.9)` = Dark navy/blue
- Nav text color: `#0b1726` = Also dark navy  
- **Contrast Ratio:** ~1.1:1 (FAIL - needs 4.5:1 minimum for WCAG AA)
- **Result:** Text is nearly invisible on dark background

---

## Fix Required

### Option 1: Light Text (Recommended - Matches Topbar)
Change navigation text to white/light:

```css
.wp-site-blocks > header .wp-block-navigation-item__content {
  color: rgba(255, 255, 255, .92) !important;  /* Light */
  font-size: 14px !important;
  font-weight: 700 !important;
}

.wp-site-blocks > header .wp-block-navigation-item__content:hover {
  background: rgba(255, 255, 255, .12) !important;
  color: rgba(255, 255, 255, .98) !important;
}
```

**Why this works:**
- White text on dark background = 16:1+ contrast ratio ✓
- Matches topbar styling (`.lg9-topbar` already uses white text correctly)
- Professional dark header appearance maintained

### Option 2: Light Background (Alternative)
```css
.wp-site-blocks > header.wp-block-template-part {
  background: rgba(255, 255, 255, .97) !important;
  backdrop-filter: blur(13px);
  border-bottom: 1px solid #dbe7f2;
}

.wp-site-blocks > header .wp-block-navigation-item__content {
  color: var(--lg-ink) !important;     /* Dark text works on light bg */
}
```

---

## Color Inventory - All Sites

### Topbar (`.lg9-topbar`) - ✓ CORRECT
```
Background: linear-gradient(90deg, #041224 0%, #0a315f 60%, #0f4a86 100%)
Text: rgba(255, 255, 255, .96) — WHITE
Contrast: 16.5:1 ✓ PASS
```

### Main Header (`.wp-site-blocks > header`) - ✗ NEEDS FIX
```
Background: rgba(4,20,40,.9) — DARK
Text: #0b1726 — ALSO DARK
Contrast: ~1.1:1 ✗ FAIL
```

### Investor Pages Hero (`.ir-shell .ir-hero`) - ✓ CORRECT
```
Background: linear-gradient(118deg, rgba(5,16,28,.94)...)  — DARK
Text: #fff — WHITE
Contrast: 16:1+ ✓ PASS
```

### Investor Pages Content (`.ir-shell .ir-source-content`) - ✓ CORRECT
```
Background: #fff or #edf6ff — LIGHT
Headings: #0c2238 — DARK
Body: #29445f — DARK  
Contrast: 8.2:1 ✓ PASS
```

### Portal Dashboard Sidebar (`.lgp-saas-sidebar`) - ✓ CORRECT
```
Background: #023E8A — DARK
Text: #e5eefb — LIGHT BLUE
Hover: #ffffff — WHITE
Contrast: 11.5:1 ✓ PASS
```

---

## Root CSS Variables (Reference)

```css
:root {
  --lg-bg: #f2f7fb;              /* Light blue-gray background */
  --lg-surface: #ffffff;         /* White */
  --lg-ink: #0b1726;             /* Dark navy - for light backgrounds */
  --lg-ink-soft: #2f455c;        /* Muted blue-gray - for light backgrounds */
  --lg-blue: #0052ab;            /* Primary blue */
  --lg-cyan: #00a9dd;            /* Cyan accent */
  --lg-navy: #041428;            /* Dark navy - for dark backgrounds */
  --lg-midnight: #082340;        /* Darkest - for darkest backgrounds */
}
```

---

## WCAG AAA Compliance Targets

| Element | Required Ratio | Current | Status |
|---------|---|---|---|
| Main header nav | 4.5:1 | ~1.1:1 | ✗ FAIL |
| Topbar | 4.5:1 | 16.5:1 | ✓ PASS |
| Investor hero | 4.5:1 | 16:1+ | ✓ PASS |
| Investor content | 4.5:1 | 8.2:1 | ✓ PASS |
| Portal sidebar | 4.5:1 | 11.5:1 | ✓ PASS |

---

## Pages Needing Verification

1. ✓ **Investor Page** - All colors correct
2. ✗ **Main Site Header** - Navigation text invisible
3. ✓ **Topbar** - Correct (white on dark)
4. ✓ **Portal Sidebar** - Correct (light on dark)
5. ⚠️ **Portal Support Dashboard** - Needs full review
6. ⚠️ **Portal Partner Dashboard** - Needs full review

---

## Recommended Fix Order

1. **Priority 1 (Critical):** Fix main header navigation text color
2. **Priority 2 (High):** Full page audit of portal dashboards
3. **Priority 3 (Medium):** Test all color states (hover, active, focus, disabled)
4. **Priority 4 (Low):** Mobile responsiveness color testing

---

## Testing URLs

- Main site: https://loungenie.com/staging/
- Investor page: https://loungenie.com/staging/index.php/investors/
- Portal support: https://loungenie.com/staging/index.php/agent-dashboard/
- Portal partner: https://loungenie.com/staging/index.php/partner-dashboard/

---

## Implementation

File location: `/wp-content/plugins/loungenie-portal/assets/css/portal-no-gradient.css`

Search for: `.wp-site-blocks > header .wp-block-navigation-item__content`

Replace with light text color to fix the visibility issue.
