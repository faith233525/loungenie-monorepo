# Pool-Safe-Portal

This repository hosts the LounGenie Portal WordPress plugin and its documentation.

## Quick Links
- Plugin README: `loungenie-portal/README.md`
- Active setup & guides: `loungenie-portal/SETUP_GUIDE.md`, `loungenie-portal/PRODUCTION_DEPLOYMENT.md`, `loungenie-portal/ENTERPRISE_FEATURES.md`
- Docs index: `docs/INDEX.md`
- Docs archive: `docs/archive/`
- Demo pages: `docs/demos/`

## Development
- The plugin lives under `loungenie-portal/`.
- Run tests:

```bash
cd loungenie-portal
composer install
php vendor/bin/phpunit --no-coverage
```

## Notes
- Non-essential delivery/audit documents were archived under `docs/archive/` to keep the repository root clean.
- Demo HTML pages were moved under `docs/demos/`.
