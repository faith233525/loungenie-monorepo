# LounGenie Portal

Production-ready WordPress plugin for partner and support portals with shared-hosting resilient CSS isolation and robust security.

> **✅ PRODUCTION READY** | Tests: 20/20 PASSED | Grade: A+ | Run validation: `php FINAL-20-TEST-SUITE.php`

## Quick links

- Production status: [PRODUCTION_DEPLOYMENT_READY.md](PRODUCTION_DEPLOYMENT_READY.md)
- Partner guide: [PARTNER_DEPLOYMENT_GUIDE.md](PARTNER_DEPLOYMENT_GUIDE.md)
- Rollback: [ROLLBACK_PLAN.md](ROLLBACK_PLAN.md)
- Monitoring: [MONITORING_AND_LOGGING.md](MONITORING_AND_LOGGING.md)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Key URLs

- `/portal` — main portal shell
- `/portal/login` — login choice
- `/partner-login` — partner credential login
- `/support-login` — Microsoft SSO entry

## Development

- PHP Coding Standards: `phpcs.xml.dist` (WordPress-Core/Docs/Extra)
- ESLint config: `.eslintrc.json`
- EditorConfig: `.editorconfig`

Run optional checks locally if desired:

```bash
phpcs
# or
eslint assets/js --ext .js
```

## Notes

- CSS isolation via `assets/css/lgp-reset.css` and `.lgp-portal-container` wrapper
- Assets are enqueued only on portal routes to avoid theme bleed across the site
