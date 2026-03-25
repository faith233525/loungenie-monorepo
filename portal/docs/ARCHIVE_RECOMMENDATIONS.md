# Archive Recommendations

This document lists non-essential, redundant, or delivery-phase documents that can be archived to reduce repository clutter while retaining history.

Move the following files to `docs/archive/` (create the folder if missing) or remove if you prefer a lean repo:

## Root-level delivery artifacts
- ARCHITECTURAL_AUDIT_REPORT.md
- AUDIT_FINDINGS.txt
- AUDIT_SUMMARY.md
- CHANGES_SUMMARY.txt
- COMPLETE_SUMMARY.md
- CONSOLIDATION_COMPLETE.md
- EXECUTION_SUMMARY.md
- FINAL_REPORT.txt
- FINAL_VERIFICATION_REPORT.md
- IMPLEMENTATION_COMPLETE.md
- PHASE_1_DELIVERY_SUMMARY.md
- PHASE_2_3_IMPLEMENTATION_PLAN.md
- PHASE_2A_COMPLETION_SUMMARY.md
- PLAN_NEXT_STEPS.md
- PLUGIN_REVIEW.md
- README_AUDIT.md
- RECOMMENDATIONS.md
- REPOSITORY_REVIEW.md
- SECURITY_HARDENING_PHASE_1_3_SUMMARY.md
- SERVER_SETUP.md (keep if actively used; otherwise archive)
- SHARED_SERVER_COMPLETE.md
- SHARED_SERVER_COMPLETE.txt
- SHARED_SERVER_TEST_SUITE.md
- SUPPORT_TICKET_COMPLETE_IMPLEMENTATION_MANIFEST.md
- SUPPORT_TICKET_COMPLETE_PACKAGE.md
- SUPPORT_TICKET_DELIVERY_SUMMARY.md
- SUPPORT_TICKET_FORM_GUIDE.md
- SUPPORT_TICKET_IMPLEMENTATION_CHECKLIST.md
- SUPPORT_TICKET_IMPLEMENTATION_SUMMARY.md
- SUPPORT_TICKET_INDEX.md
- SUPPORT_TICKET_INTEGRATION.md
- SUPPORT_TICKET_README.md (if duplicated by core README)
- TEST_RESULTS.txt
- UNIFIED_IMPLEMENTATION_PLAN.md
- UPDATE_COMPLETE.md
- WPCS_WORKFLOW_COMPLETE.md

## Documentation indexes (consider merging)
- DOCUMENTATION_INDEX.md — consolidate into the main `README.md` or a `docs/INDEX.md`.

## HTML demo pages (if not used in production)
- LOGIN_ENHANCED.html
- PORTAL_CYAN_TEAL.html
- ROLE_SWITCHER_DEMO.html
- index.html

## Root-level scripts (ephemeral / non-critical)
- comprehensive_validation.sh
- comprehensive_fix.php
- fix_comments.php
- ultra_comprehensive_test.sh
- ULTRA_COMPREHENSIVE_TEST_REPORT.md

Note: Some of these documents are helpful for historical context. Archiving (not deleting) keeps them accessible without bloating the top-level directory.

## Suggested Folder Structure
```
docs/
  archive/
    (moved legacy audit and delivery artifacts)
  guides/
    (setup guides, runbooks used by developers)
  api/
    (API references and endpoint docs)
```

## Process
1. Create `docs/archive/` folder.
2. Move files listed above into `docs/archive/`.
3. Ensure `README.md` links to `docs/archive/` for reference.
4. Keep actively maintained docs in `docs/` or component-specific folders.

## Benefits
- Cleaner repository root
- Faster navigation for contributors
- Lower cognitive load when onboarding
- Clear separation of active vs historical documentation

