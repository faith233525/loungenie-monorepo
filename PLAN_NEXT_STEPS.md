# Pool-Safe-Portal — Action Plan (Q1 2026)

## Objectives
- Stabilize and harden the new portal updates (router, tickets, map, SSO, email ingest, color aggregation)
- Improve standards compliance, performance, and security on shared hosting
- Streamline CI/CD and release process

## Immediate Checks (done)
- Tests: 181/181 passing
- Repository review added: `REPOSITORY_REVIEW.md`
- Push status: `main` up-to-date

## Phase 1 — Standards & Security Hardening
1. Prepared SQL everywhere
   - Replace string-interpolated SQL with `$wpdb->prepare()` in `api/dashboard.php`, `api/map.php`, `api/tickets.php`, templates flagged by PHPCS
2. Input handling
   - Use `wp_unslash()` before sanitizing `$_SERVER` inputs; ensure consistent `sanitize_*` usage
3. Output escaping in templates
   - Replace raw `_e()` with escaped variants; use `esc_html_e`, `esc_attr`, `esc_url`
4. WPCS hygiene
   - Yoda conditions, docblocks, inline comment punctuation, spacing alignment (advisory)
5. Security headers
   - Confirm CSP, HSTS, X-Frame-Options behavior in `LGP_Security` for portal views only

## Phase 2 — Performance & Caching
1. Query caching
   - Add `wp_cache_get/wp_cache_set` or transients for hot queries flagged by PHPCS (dashboard, map, tickets list)
2. Color aggregation
   - Validate `top_colors` refresh on unit changes; batched refresh using `batch_refresh()` for large imports
3. Map performance
   - Ensure pagination/limit for `map/units` and role-based scoping remains fast
4. Attachments
   - Validate MIME/type checks, size enforcement, secure storage path and retention policies

## Phase 3 — CI/CD & Release
1. CI
   - Add PHPCS job (advisory) + PHPUnit job, publish artifacts (`test-results`, `phpcs-summary`)
2. Release tagging
   - Tag next release (e.g., v1.8.2) with attached `REPOSITORY_REVIEW.md`
3. Issue tracking
   - Create GH issues for Phase 1/2 tasks (prepared SQL, escaping, caching)
4. Docs
   - Consolidate and link key docs: `DEPLOYMENT_CHECKLIST.md`, `ENTERPRISE_FEATURES.md`, `WPCS_STRATEGY.md`

## Phase 4 — Feature Verifications
1. Microsoft SSO
   - Verify authorize/callback workflow; token refresh; role assignment to `lgp_support`
2. Email ingest (Graph/POP3)
   - Validate transient lock, delta token update, idempotency; attachments saved to protected dir
3. Tickets
   - Transaction safety (START/COMMIT/ROLLBACK), audit logging; reply thread history
4. HubSpot sync
   - Test company/ticket sync and association; retry queue; admin settings page
5. Shared hosting rules
   - Confirm no websockets/background listeners, response-time budget, rate limiting

## How to run (local/dev)
```bash
# Tests
cd loungenie-portal
php vendor/bin/phpunit --no-coverage

# Standards (informational)
cd /workspaces/Pool-Safe-Portal/loungenie-portal
./vendor/bin/phpcs -d memory_limit=512M -s includes api templates *.php
```

## Milestones & Ownership
- Week 1–2: Phase 1 hardening
- Week 3–4: Phase 2 performance work
- Week 5: CI/CD, Release
- Week 6: Feature verification & doc consolidation

## Notes
- `.env` must remain untracked; use `.env.example` for placeholders
- Shared hosting constraints are strict—keep request-bound logic only
- Use audit logging on all data mutations for compliance
