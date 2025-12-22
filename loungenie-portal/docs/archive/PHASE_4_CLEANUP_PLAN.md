# Phase 4: Folder & Asset Cleanup Plan

## Analysis

**Total Plugin Size:** 29MB  
**Largest Components:**
- vendor/ (26MB) - Composer dependencies
- docs/ (680KB) - Development documentation
- includes/ (508KB) - Class files
- assets/ (476KB) - CSS, JS, images
- scripts/ (336KB) - Development/offline tools
- templates/ (224KB) - HTML templates

---

## Production vs. Development Files

### Keep in Production ✅

**Runtime Code:**
- api/ (96KB) - REST API endpoints ✅
- includes/ (508KB) - Core classes ✅
- templates/ (224KB) - Portal templates ✅
- assets/ (476KB) - CSS, JS (production) ✅
- roles/ (12KB) - User role definitions ✅
- languages/ - Translation files ✅

**Configuration:**
- loungenie-portal.php (main file) ✅
- composer.json - Dependency manifest ✅
- composer.lock - Dependency lock ✅
- package.json - Asset dependencies ✅
- package-lock.json - Package lock ✅
- uninstall.php - Cleanup on uninstall ✅
- phpunit.xml - Test configuration ✅
- phpcs.xml - Code standards config ✅

**Documentation (Essential):**
- README.md ✅
- CHANGELOG.md ✅
- CONTRIBUTING.md ✅
- SETUP_GUIDE.md ✅
- DEPLOYMENT_CHECKLIST.md ✅

### Exclude from Production ZIP ❌

**Development/Offline:**
- scripts/ (336KB) - Offline development tools
- wp-cli/ - Custom CLI commands (non-essential)

**Testing:**
- tests/ - PHPUnit test suite (development)
- test-*.php files

**Documentation:**
- docs/ (680KB) - All development documentation
- *.md files (except essentials above)

**Demo/Preview:**
- preview-demo.html
- PRODUCTION_PORTAL_PREVIEW.html

**Version Control:**
- .git, .gitignore
- vendor/ (OPTIONAL: include or provide composer.json)

---

## Cleanup Actions

### 1. Remove Experimental/Temporary Files

```bash
rm -f test-load.php
rm -f preview-demo.html
rm -f PRODUCTION_PORTAL_PREVIEW.html
rm -f test-results-initial.txt
rm -f server-router.php (if not in use)
rm -f local-wp/ (if separate from plugin)
```

### 2. Verify Essential Directories

```bash
# Keep these:
- api/                (REST endpoints)
- assets/            (CSS, JS, images)
- includes/          (Class definitions)
- templates/         (HTML templates)
- roles/             (User roles)
- languages/         (i18n translations)

# Keep for meta:
- wp-admin/          (Custom admin pages)

# Exclude from ZIP:
- tests/             (Unit testing)
- scripts/           (Offline tools)
- docs/              (Development docs - in repo but not ZIP)
- vendor/            (Optional - provide composer.json)
- node_modules/      (If any)
```

### 3. Check Asset Optimization

```bash
# CSS files (should be minified for production)
ls -lh assets/css/

# JavaScript files (should be minified for production)
ls -lh assets/js/

# Images (check for optimization)
ls -lh assets/images/
```

---

## File Size Impact

| Component | Before | After | Change | Status |
|-----------|--------|-------|--------|--------|
| /docs | 680KB | 0 | -680KB | Excluded from ZIP |
| /tests | ~50KB | 0 | -50KB | Excluded from ZIP |
| /scripts | 336KB | 0 | -336KB | Excluded from ZIP |
| /preview-demo.html | 32KB | 0 | -32KB | Deleted |
| Total ZIP | ~29MB | ~28MB | -1.1MB | ✅ Leaner |

**ZIP will be ~28MB (includes vendor/)**

---

## Verification Checklist

- [ ] No test files in production ZIP
- [ ] No demo/preview files in ZIP
- [ ] Documentation organized in /docs (not in ZIP)
- [ ] Essential docs included (README, SETUP, DEPLOY, CHANGELOG)
- [ ] Assets optimized (CSS/JS minified if applicable)
- [ ] No node_modules in ZIP
- [ ] composer.lock included (dependencies locked)
- [ ] Version correctly set to 1.8.1

---

## Next Phase

Phase 5: QA & Verification - Run all validation checks

