# LounGenie Portal WordPress Readiness Checklist

## Core & Compatibility
- [ ] Plugin headers (Requires at least, Requires PHP, Version) confirmed
- [ ] Text domain matches slug and `load_plugin_textdomain()` wired
- [ ] Activation/deactivation hooks stable; uninstall cleans options/tables as documented

## Security & Permissions
- [ ] Every REST/AJAX route has `permission_callback` with capabilities/roles
- [ ] Partner access scoped to own company; support unrestricted
- [ ] All inputs sanitized/validated; errors return `WP_Error` with status
- [ ] Nonces on forms/AJAX; fallbacks only in tests
- [ ] Uploads validated (mime/extension/size), random filenames, no executables

## Performance & Shared Hosting
- [ ] Boot kept light; load routes/assets only on portal pages
- [ ] External HTTP has 5–10s timeouts and graceful errors
- [ ] No writes outside uploads; caching via transients/db only
- [ ] Queries prepared; dashboards free of N+1s

## Routing & UX
- [ ] Portal routes/query vars avoid admin interference
- [ ] Templates escaped (`esc_html/esc_attr/esc_url`) and accessible (skip links, ARIA)
- [ ] Assets versioned and only enqueued where needed; no duplicate/unused bundles

## SSO (Microsoft)
- [ ] Settings documented; graceful errors for nonce/token/graph issues
- [ ] `home_url` fallbacks safe for tests; timeouts/logging light

## Logging & Ops
- [ ] Logger lightweight by default; optional toggle for verbosity
- [ ] README/SETUP notes for shared hosting: PHP extensions, upload limits, cron, debug toggle
- [ ] `.env` placeholders (if used) for SSO secrets; secrets not committed

## Testing & QA
- [ ] PHPUnit suite green
- [ ] PHPCS (WPCS) clean or known exceptions noted
- [ ] Manual smoke: portal (support/partner), login, uploads, SSO error path, REST 401/403
- [ ] HTML/CSS/JS sanity checked; map view renders without console errors
