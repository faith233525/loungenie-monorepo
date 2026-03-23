# EXACT CODE LOCATION & CHANGE - Visual Guide

## Step 1: Find the Exact Location

### File Path
```
/wp-content/plugins/loungenie-portal/assets/css/portal-no-gradient.css
```

### Search String
Use Ctrl+F to find: **`.wp-site-blocks > header .wp-block-navigation-item__content {`**

---

## Step 2: Locate the Color Line

### You'll find this section:

```css
.wp-site-blocks > header.wp-block-template-part {
    position: sticky;
    top: 0;
    z-index: 990;
    background: rgba(4,20,40,.9);
    border-bottom: 1px solid rgba(170,205,238,.3);
    backdrop-filter: blur(13px);
}

.wp-site-blocks > header .lg9-head {
    max-width: 1280px;
    margin: 0 auto;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.wp-site-blocks > header .wp-block-navigation-item__content {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--lg-ink) !important;           ← ⚠️ CHANGE THIS LINE ⚠️
    transition: all .2s ease;
}
```

---

## Step 3: The Exact Change Required

### BEFORE (Current - Broken)
```css
.wp-site-blocks > header .wp-block-navigation-item__content {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--lg-ink) !important;                    ❌ DARK TEXT ON DARK BG
    transition: all .2s ease;
}
```

### AFTER (Fixed - Correct)
```css
.wp-site-blocks > header .wp-block-navigation-item__content {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: rgba(255, 255, 255, .92) !important;        ✓ LIGHT TEXT ON DARK BG
    transition: all .2s ease;
}
```

### What Changed
- **FROM:** `color: var(--lg-ink) !important;`
- **TO:** `color: rgba(255, 255, 255, .92) !important;`
- **Lines to change:** Just **1 line**

---

## Step 4: Add Hover & Active States (Optional but Recommended)

After the above rule, add:

```css
/* NEW: Hover state */
.wp-site-blocks > header .wp-block-navigation-item__content:hover {
    background: rgba(255, 255, 255, .12) !important;
    color: rgba(255, 255, 255, .98) !important;
}

/* NEW: Active/current page state */
.wp-site-blocks > header .wp-block-navigation-item__content[aria-current="page"] {
    background: rgba(255, 255, 255, .18) !important;
    color: #fff !important;
}
```

---

## Step 5: Also Update This (Related Section)

Find: `.wp-site-blocks > header .wp-block-navigation-submenu__toggle`

Change this line:
```css
/* BEFORE */
.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color:var(--lg-ink) !important;                     ❌ CHANGE THIS
}

/* AFTER */
.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color: rgba(255, 255, 255, .92) !important;        ✓ LIGHT TEXT
}
```

---

## Complete Replacement Block

If you want to replace the entire section at once, use this:

### OLD CODE (Remove)
```css
.wp-site-blocks > header .wp-block-navigation-item__content {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--lg-ink) !important;
    transition: all .2s ease;
}

.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color: var(--lg-ink) !important;
}
```

### NEW CODE (Replace with)
```css
.wp-site-blocks > header .wp-block-navigation-item__content {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: rgba(255, 255, 255, .92) !important;
    transition: all .2s ease;
}

.wp-site-blocks > header .wp-block-navigation-item__content:hover {
    background: rgba(255, 255, 255, .12) !important;
    color: rgba(255, 255, 255, .98) !important;
}

.wp-site-blocks > header .wp-block-navigation-item__content[aria-current="page"] {
    background: rgba(255, 255, 255, .18) !important;
    color: #fff !important;
}

.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color: rgba(255, 255, 255, .92) !important;
}
```

---

## Color Reference (For Copy-Paste)

### Colors Used:
```
Light text:     rgba(255, 255, 255, .92)     ← Main nav text
Lighter hover:  rgba(255, 255, 255, .98)     ← Hover text (brighter)
Full white:     #fff                          ← Active state
Light hover bg: rgba(255, 255, 255, .12)     ← Hover background
Active bg:      rgba(255, 255, 255, .18)     ← Active background
```

---

## Verification After Change

### Expected Result:
✓ Header navigation text changes from **invisible** to **white**
✓ Text reads clearly on dark background
✓ Hover effects show lighter white
✓ Active page has highlighted background

### Test URLs:
- Main: https://loungenie.com/staging/
- Investors: https://loungenie.com/staging/index.php/investors/
- Portal: https://loungenie.com/staging/index.php/agent-dashboard/

### How to Test:
1. Apply the change
2. Clear browser cache (Ctrl+Shift+R)
3. Load https://loungenie.com/staging/
4. Look at the top navigation - should see **white text** now
5. Hover over a nav item - should get **brighter** effect
6. Navigate to a page - current page link should have **light background**

---

## If You Get Stuck

1. **Can't find the file?** → Use FTP explorer or WordPress file manager
2. **Can't find the code?** → Search for `wp-block-navigation-item__content`
3. **Not sure what changed?** → Look for the color property, should say `rgba(255, 255, 255, .92)` after fix
4. **Change isn't showing?** → Clear WordPress cache AND browser cache, then reload
5. **Want to undo?** → Change the color back to `var(--lg-ink)` and save again

---

## Alternative: Quick CSS Injection

If you don't want to edit the plugin file, add this to **Themes → Customize → Additional CSS**:

```css
/* Override: Fix header navigation visibility */
.wp-site-blocks > header .wp-block-navigation-item__content {
    color: rgba(255, 255, 255, .92) !important !important;
}

.wp-site-blocks > header .wp-block-navigation-item__content:hover {
    background: rgba(255, 255, 255, .12) !important !important;
    color: rgba(255, 255, 255, .98) !important !important;
}

.wp-site-blocks > header .wp-block-navigation-item__content[aria-current="page"] {
    background: rgba(255, 255, 255, .18) !important !important;
    color: #fff !important !important;
}

.wp-site-blocks > header .wp-block-navigation-submenu__toggle {
    color: rgba(255, 255, 255, .92) !important !important;
}
```

(Using `!important !important` makes sure it overrides the plugin CSS)

---

## Status

✓ Ready to implement
✓ All code locations identified
✓ Easy copy-paste format provided
✓ Verification steps ready
✓ Rollback plan available

**Next Step:** Pick your method above and apply the change!
