# LounGenie Portal - Full Production Prep Automation Plan

## Phase 1: Verify All Critical Fixes ✅

- [x] Version update (1.8.0 → 1.8.1)
- [x] Class initialization guards
- [x] Error logging simplification
- [x] JavaScript scope safety
- [x] Help guides auth refactor

**Status:** ALL CRITICAL FIXES VERIFIED & APPLIED

---

## Phase 2: Apply High-Priority Fixes

**High Priority Issues from Audit:**
1. Add null safety checks (2 files: api/dashboard.php, api/map.php)
2. Replace in_array() role checks with LGP_Auth (api/help-guides.php - DONE)
3. Add null checks after database queries (2 files)

**Action:** Apply remaining null safety & query result checks

---

## Phase 3: Markdown File Organization

**Production Docs (Keep in root):**
- README.md ✅
- CHANGELOG.md ✅
- CONTRIBUTING.md ✅
- SETUP_GUIDE.md ✅
- DEPLOYMENT_CHECKLIST.md ✅
- ENTERPRISE_FEATURES.md ✅
- FILTERING_GUIDE.md ✅

**Development Docs (Move to /docs):**
- All CODE_AUDIT_*.md files (7 files)
- ARCHITECTURE.md
- IMPLEMENTATION_*.md files (5 files)
- EMAIL_TO_TICKET_*.md files (3 files)
- CSV_*.md files (3 files)
- CUSTOM_LOGIN_*.md files (3 files)
- UNIT_COLOR_*.md files (4 files)
- *_SUMMARY.md files (10+ files)
- Test & QA guides (COMPREHENSIVE_TESTING_GUIDE.md, etc.)

**Candidates for Removal (Duplicates/Outdated):**
- COMPREHENSIVE_AUDIT_AND_FIXES.md (superseded by CODE_AUDIT_AND_FIXES.md)
- README_ENHANCEMENTS.md (merged into README.md)
- IMPLEMENTATION_COMPLETE.md (historical, see CHANGELOG.md)
- ZIP_DEPLOYMENT_READY.md (historical)
- FINAL_CLEANUP_VERIFICATION.md (historical)

---

## Phase 4: Folder & Assets Cleanup

**Keep:**
- /includes - All class files ✅
- /templates - Portal templates ✅
- /api - REST API endpoints ✅
- /assets - CSS, JS (check for unused) ✅
- /roles - User role definitions ✅
- /languages - i18n strings ✅
- /scripts - Utilities & offline dev ✅

**Review:**
- /tests - Keep in repo, exclude from ZIP
- /vendor - Composer deps (include in ZIP for production)
- /docs - Move audit/dev docs here
- /wp-admin - Custom admin pages
- /wp-cli - CLI commands

---

## Phase 5: QA & Verification

Commands to run:
- `composer run test` - PHPUnit tests
- `php -l loungenie-portal.php` - Syntax check
- `npm run build` or asset compilation
- Manual smoke tests (frontend, login, APIs)

---

## Phase 6: Production ZIP

**Include:**
- loungenie-portal.php (main file)
- All runtime code (/api, /includes, /templates, /assets, /roles, /languages, /scripts)
- /vendor (Composer dependencies)
- Production docs (README.md, CHANGELOG.md, SETUP_GUIDE.md, DEPLOYMENT_CHECKLIST.md)
- readme.txt (WordPress.org plugin file)

**Exclude:**
- /docs (dev docs)
- /tests (testing)
- /vendor (optional - provide composer.json for install)
- Hidden files (.git, .env, etc.)
- Node modules (if any)
- Documentation markdown files (except essentials)

**Output:** `loungenie-portal-1.8.1.zip`

---

## Phase 7: Final Report

Document:
1. All fixes applied (before/after code snippets)
2. Files modified (line counts, impact)
3. Markdown files reorganized (counts by category)
4. Verification results (all passing)
5. Production ZIP contents
6. Deployment instructions
7. Security checklist

---

## Timeline Estimate

- Phase 1 (Verify): 5 min ✅
- Phase 2 (High Priority): 20 min
- Phase 3 (Markdown): 30 min
- Phase 4 (Cleanup): 15 min
- Phase 5 (QA): 20 min
- Phase 6 (ZIP): 10 min
- Phase 7 (Report): 30 min

**Total: ~2.5 hours**

