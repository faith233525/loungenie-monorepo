# Contributing to LounGenie Portal

Thank you for contributing to the LounGenie Portal WordPress plugin! This document outlines our development standards and workflow.

## Code Quality Standards

### WordPress Coding Standards (WPCS)

We follow the [WordPress Coding Standards](https://developer.wordpress.org/plugins/wordpress-org/how-your-plugin-gets-reviewed/) with the following policy:

- **New code must comply** with WordPress Coding Standards (WPCS).
- **Existing violations** in legacy code are tracked and will be incrementally addressed.
- **CI checks**: Tests and PHP linting are **required to pass**; PHPCS summary is **advisory** and published as an artifact.

### Running Local Checks

From `loungenie-portal/`:

```bash
# Run full test suite (required for PR)
composer run test

# Check WordPress Coding Standards (informational)
composer run cs

# Auto-fix auto-fixable WPCS violations
composer run cbf
```

### Before Submitting a PR

1. **Run tests**: `composer run test` — all 138 tests must pass.
2. **Check WPCS**: `composer run cs` — no new violations in your changes.
3. **Auto-fix**: `composer run cbf` — apply safe fixes automatically.
4. **Review escaping**: Ensure output in templates uses `esc_html()`, `esc_attr()`, or `esc_url()`.
5. **Verify i18n**: Ensure user-facing strings use `__()`, `_e()`, or similar with `'loungenie-portal'` text domain.

## Git Workflow

### Commits

- Use clear, descriptive commit messages (e.g., `feat(api): add new gateway endpoint`)
- Separate commits by concern (don't mix style and logic changes)
- Use conventional commit prefixes:
  - `feat:` new feature
  - `fix:` bug fix
  - `style:` code formatting (WPCS, spacing)
  - `test:` test additions/updates
  - `docs:` documentation
  - `chore:` dependencies, CI, tooling

### Pull Requests

1. Create a feature branch: `git checkout -b feature/description`
2. Make focused changes (one concern per branch)
3. Push and open a PR against `main`
4. CI will run tests and generate a PHPCS summary
5. Address any test failures or security issues
6. Await review and merge

## Testing

- All new features must have corresponding tests
- Use PHPUnit + Brain Monkey for mocking WordPress functions
- Run `composer run test` before submitting (expect 100% pass rate)

## Security

- Sanitize all user inputs: `sanitize_text_field()`, `sanitize_email()`, etc.
- Escape all outputs: `esc_html()`, `esc_attr()`, `esc_url()`
- Use `$wpdb->prepare()` for all database queries
- Verify nonces on form submissions: `wp_verify_nonce()`
- Check permissions: `current_user_can()`, `wp_get_current_user()`

## Internationalization (i18n)

- Use `__('string', 'loungenie-portal')` for translatable strings
- Use `_e('string', 'loungenie-portal')` for echoed strings
- Use `_x('string', 'context', 'loungenie-portal')` for contextual translation

## Questions?

- Check existing issues or documentation
- Open an issue for bugs or feature requests
- Ask in PR comments for clarification

Thank you for helping make LounGenie Portal better!
