# Pool-Safe-Portal: Design System Implementation Guide

**Current Status:** Security & Performance Hardening Complete ✅ | Ready for Design Phase  
**Next Phase:** Implement modern design system (5-week roadmap)

---

## What Was Just Completed

✅ **Security Hardening**
- Server-side upload validation (MIME type checking)
- Rate limiting on APIs (tickets 5/hr, attachments 10/hr)
- Automatic attachment cleanup (90-day retention cron)
- HubSpot sync queue bounds (cap at 500 items)

✅ **Code Quality**
- PHPCS baseline audit completed
- Critical security gaps resolved
- Deployment documentation updated

✅ **Design System Blueprint**
- Modern typography system documented
- WCAG AA-compliant color palette specified
- Component library sketched (buttons, cards, forms, tables, modals)
- Responsive grid layout defined
- Accessibility & motion guidelines prepared

---

## Design System Implementation (5-Week Roadmap)

### Week 1: Design Tokens
**Deliverable:** `assets/css/design-tokens.css` (updated) + `DESIGN_TOKENS.md`

**Tasks:**
1. Update color palette in CSS (primary #0066CC, neutrals, semantics)
2. Define typography scale with system font stack
3. Create spacing scale (8px base)
4. Document all tokens in reference guide
5. Create CSS variable aliases for backward compatibility

**Files to modify:**
- `assets/css/design-tokens.css` — replace with new token system
- `assets/css/variables.css` — ensure compatibility aliases

**Time:** 5 hours

---

### Week 2: Layout & Components
**Deliverable:** Refined header/sidebar + modern button/card/form styles

**Tasks:**
1. Update header to 64px (from 80px) with modern spacing
2. Refactor sidebar (260px width, responsive hiding at 1024px)
3. Style buttons (primary, secondary, danger variants)
4. Create modern card component with proper shadows
5. Design form inputs with focus states and inline validation
6. Build table component with sortable headers
7. Create badge and alert components

**Files to modify:**
- `assets/css/portal-shell.css` — header/sidebar refactor
- `assets/css/portal-components.css` — component styling
- `templates/portal-shell.php` — semantic HTML, data attributes
- `templates/components/card.php` — modern styling

**Time:** 12 hours

---

### Week 3: Accessibility & Motion
**Deliverable:** Full WCAG AA compliance + subtle animations

**Tasks:**
1. Verify color contrast (4.5:1 minimum for normal text)
2. Add focus states to all interactive elements
3. Implement keyboard navigation (Tab through elements, Escape closes modals)
4. Create animations (fade-in, slide-up, stagger for lists)
5. Add prefers-reduced-motion support (disable animations for users who prefer it)
6. Test with screen reader (NVDA/JAWS simulation)
7. Create skip-link functionality

**Files to modify/create:**
- `assets/css/accessibility.css` — focus states, WCAG AA checks
- `assets/css/motion.css` — animations, transitions, prefers-reduced-motion
- `assets/js/portal.js` — keyboard nav, focus trap in modals

**Time:** 10 hours

---

### Week 4: Cross-Browser & Responsive Testing
**Deliverable:** Verified across all major browsers and devices

**Tasks:**
1. Test on Chrome, Firefox, Safari, Edge (latest versions)
2. Test on iOS Safari, Chrome Android
3. Verify responsive behavior at breakpoints (sm/md/lg/xl)
4. Run Lighthouse audit (target 95+ on performance, accessibility)
5. Check Core Web Vitals (LCP, FID, CLS)
6. Performance profiling (CSS/JS minification, unused code removal)
7. Document any browser-specific fixes

**Tools:**
- Lighthouse (Chrome DevTools)
- axe DevTools (accessibility)
- Responsively App (multi-device testing)
- WebPageTest (Core Web Vitals)

**Time:** 8 hours

---

### Week 5: Deployment & Documentation
**Deliverable:** Updated DEPLOYMENT.md + component guide + design standards

**Tasks:**
1. Update DEPLOYMENT.md with design system guidelines
2. Create COMPONENT_GUIDE.md (usage examples for developers)
3. Document design tokens in searchable format
4. Create style guide website or Markdown reference
5. Update README with design links
6. Train team on new design system
7. Set up pre-commit CSS linter (optional)

**Files to modify/create:**
- `DEPLOYMENT.md` — add design guidelines section
- `COMPONENT_GUIDE.md` — NEW
- `DESIGN_TOKENS.md` — NEW
- `README.md` — add design system section

**Time:** 6 hours

---

## Quick Start: Implementing Week 1

### Step 1: Backup Current Tokens
```bash
cp assets/css/design-tokens.css assets/css/design-tokens.css.backup
```

### Step 2: Create New Token File
Update `assets/css/design-tokens.css` with:

```css
:root {
  /* COLORS */
  --color-primary:           #0066CC;
  --color-primary-hover:     #0052A3;
  --color-primary-light:     #E6F0FF;
  --color-primary-dark:      #003D7A;
  
  --color-bg-page:           #FFFFFF;
  --color-bg-section:        #F7F9FC;
  --color-border:            #D1D5DB;
  --color-border-light:      #E5E7EB;
  
  --color-text-primary:      #111827;
  --color-text-secondary:    #4B5563;
  --color-text-tertiary:     #9CA3AF;
  
  --color-success:           #059669;
  --color-warning:           #D97706;
  --color-danger:            #DC2626;
  
  /* TYPOGRAPHY */
  --font-family:             -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  --font-family-mono:        "SF Mono", Monaco, monospace;
  
  --type-xs:                 0.75rem;
  --type-sm:                 0.875rem;
  --type-base:               1rem;
  --type-lg:                 1.125rem;
  --type-xl:                 1.25rem;
  --type-2xl:                1.5rem;
  
  --weight-regular:          400;
  --weight-medium:           500;
  --weight-semibold:         600;
  --weight-bold:             700;
  
  --lh-tight:                1.2;
  --lh-normal:               1.5;
  --lh-relaxed:              1.75;
  
  /* SPACING */
  --space-0:                 0;
  --space-1:                 0.25rem;
  --space-2:                 0.5rem;
  --space-3:                 0.75rem;
  --space-4:                 1rem;
  --space-6:                 1.5rem;
  --space-8:                 2rem;
  --space-12:                3rem;
  
  /* LAYOUT */
  --header-height:           64px;
  --sidebar-width:           260px;
  
  /* BORDERS & SHADOWS */
  --radius-sm:               4px;
  --radius-md:               6px;
  --radius-lg:               8px;
  --radius-pill:             12px;
  
  --shadow-sm:               0 1px 3px rgba(0,0,0,0.1);
  --shadow-md:               0 4px 12px rgba(0,0,0,0.15);
  --shadow-lg:               0 20px 25px rgba(0,0,0,0.15);
  
  /* TRANSITIONS */
  --transition-fast:         150ms ease;
  --transition-base:         200ms ease;
  --transition-slow:         300ms ease;
}
```

### Step 3: Create Compatibility Aliases
Add at end of design-tokens.css to ensure old components still work:

```css
/* Backward compatibility aliases */
:root {
  --lgp-primary:             var(--color-primary);
  --lgp-background:          var(--color-bg-page);
  --lgp-text-primary:        var(--color-text-primary);
  /* ... more aliases as needed */
}
```

### Step 4: Test in Components
Update one component (e.g., button) to use new tokens:

```css
.button {
  padding: var(--space-3) var(--space-6);
  background: var(--color-primary);
  color: white;
  font-weight: var(--weight-semibold);
  border-radius: var(--radius-md);
  transition: all var(--transition-base);
}

.button:hover {
  background: var(--color-primary-hover);
  box-shadow: var(--shadow-md);
}

.button:focus {
  outline: 2px solid var(--color-primary-dark);
  outline-offset: 2px;
}
```

### Step 5: Verify No Breakage
- Load `/portal` in browser
- Check header, sidebar, buttons render correctly
- Verify no console errors
- Test on mobile (responsive)

---

## Design System Files Reference

| File | Purpose |
|------|---------|
| `assets/css/design-tokens.css` | Core color, typography, spacing tokens |
| `assets/css/portal-shell.css` | Header, sidebar, main layout |
| `assets/css/portal-components.css` | Button, card, form, table styles |
| `assets/css/accessibility.css` | Focus states, WCAG AA compliance |
| `assets/css/motion.css` | Animations, transitions, prefers-reduced-motion |
| `DESIGN_SYSTEM_UPGRADE.md` | Comprehensive design spec (in repo) |
| `COMPONENT_GUIDE.md` | Usage examples for developers (to be created) |
| `DESIGN_TOKENS.md` | Token reference guide (to be created) |

---

## How to Stay on Track

1. **Week 1:** Update tokens, test in one component
2. **Week 2:** Refactor layout (header/sidebar), update main components
3. **Week 3:** Add accessibility, animations, prefers-reduced-motion
4. **Week 4:** Test everywhere (browsers, devices, accessibility tools)
5. **Week 5:** Document, deploy, celebrate 🎉

---

## Resources & Tools

**Design & Documentation:**
- [Web Accessibility Guidelines (WCAG 2.1 AA)](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)
- [Tailwind CSS Design System Concepts](https://tailwindcss.com/docs) (for reference, not using it)

**Testing & Validation:**
- Chrome DevTools Lighthouse
- axe DevTools (accessibility)
- WebAIM Contrast Checker
- Responsively App (multi-device)

**Automation:**
- stylelint (CSS linting)
- Pa11y (accessibility testing)
- Percy (visual regression testing, optional)

---

## Success Criteria

✅ **By End of Week 5:**
- [ ] All design tokens in CSS variables (no hardcoded colors)
- [ ] WCAG AA compliance verified (color contrast, focus states, keyboard nav)
- [ ] Lighthouse score 95+ (accessibility, performance)
- [ ] Responsive on sm/md/lg/xl breakpoints
- [ ] Animations honor prefers-reduced-motion
- [ ] All components documented in COMPONENT_GUIDE.md
- [ ] Zero console warnings/errors in portal
- [ ] Team trained on new design system

---

## Questions?

Refer to [DESIGN_SYSTEM_UPGRADE.md](DESIGN_SYSTEM_UPGRADE.md) for complete specification.

**Next:** Start Week 1! Begin with design-tokens.css update.

