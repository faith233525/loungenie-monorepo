# WordPress Coding Standards (WPCS) Compliance

This plugin follows the [WordPress Coding Standards](https://developer.wordpress.org/plugins/wordpress-org/how-your-plugin-gets-reviewed/) with a pragmatic approach to legacy code and new development.

## Current Status

- **Auto-fixable violations:** 0 remaining (all applied)
- **Remaining violations:** ~611 (semantic, requiring manual review)
  - Output escaping (templates, API responses)
  - i18n compliance (text domain usage)
  - Naming/prefix conventions
  - Comparison operators and type strictness

## Strategy

### For New Code

New code **must** comply with WordPress Coding Standards before merging. Reviewers will check:

1. **Security**: Sanitization, escaping, nonces
2. **i18n**: String localization with correct text domain (`loungenie-portal`)
3. **Standards**: Naming, spacing, documentation

### For Legacy Code

Existing violations are tracked but not blocking. They will be addressed:

- Incrementally as code is touched during feature work
- In dedicated maintenance sprints when capacity allows
- With careful manual review to avoid introducing bugs

## Local Development

### Check Compliance

```bash
cd loungenie-portal
composer run cs
```

This runs PHPCS with WordPress Coding Standards against `includes/`, `api/`, `templates/`, and root PHP files.

### Auto-Fix Safe Violations

```bash
cd loungenie-portal
composer run cbf
```

PHPCBF automatically fixes ~95% of violations (spacing, formatting, whitespace). Commit these changes with a `style:` prefix.

### Manual Fixes

For escaping, i18n, and naming violations, manual review is required. Example workflow:

```bash
# 1. Review flagged violations
vendor/bin/phpcs --standard=WordPress includes/class-lgp-auth.php

# 2. Edit the file, applying fixes
# 3. Run tests to ensure behavior unchanged
composer run test

# 4. Commit with clear message
git commit -m "style(auth): add missing output escaping in is_support()"
```

## CI/CD Enforcement

- **Tests (PHPUnit):** ✅ Required to pass
- **Syntax (PHP Lint):** ✅ Required to pass
- **Standards (PHPCS):** ℹ️ Advisory; artifact published per build

Developers are expected to run `composer run cs` and `composer run cbf` locally before pushing.

## FAQ

**Q: Why aren't WPCS violations gating the build?**
A: Legacy violations are too numerous to resolve without risk of introducing bugs. New code compliance is enforced at review time; existing code is incrementally improved.

**Q: Can I ignore WPCS warnings?**
A: No. WPCS violations in new code must be fixed before merge. Use `// phpcs:ignore` only for intentional exceptions, with a comment explaining why.

**Q: What if I'm unsure how to fix a violation?**
A: Ask in the PR. Maintainers can guide or pair-program the fix.

## Resources

- [WordPress Coding Standards Handbook](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PHPCS WordPress Ruleset](https://github.com/WordPress/WordPress-Coding-Standards)
- [Escaping in WordPress](https://developer.wordpress.org/plugins/security/securing-output/)
- [Internationalization](https://developer.wordpress.org/plugins/internationalization/)
