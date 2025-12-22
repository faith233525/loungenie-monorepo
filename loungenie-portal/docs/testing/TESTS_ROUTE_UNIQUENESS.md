# Route Uniqueness Test

This repository includes an automated test that verifies all WordPress REST routes are unique by the tuple (namespace, route, method).

## What it checks
- No two registrations share the same `(namespace, route, method)` key
- Works across API files: `api/*.php` and any other classes calling `register_rest_route`

## Where it lives
- Test file: `tests/Routes/RouteUniquenessTest.php`
- Bootstrap stub: `tests/bootstrap.php` records each route key via a `register_rest_route()` stub.

## How it works
1. The bootstrap defines a lightweight stub for `register_rest_route()` that logs the key `"<namespace> <route> <METHOD>"` into a global list.
2. The test includes API classes and explicitly calls their `register_routes()` static methods.
3. The test compares the total recorded route keys vs. the number of unique keys and fails if any duplicates exist.

## Running just this test
```bash
cd loungenie-portal
php vendor/bin/phpunit --no-coverage --filter RouteUniquenessTest
```

## Caveats
- The test avoids defining `add_action()`/`add_filter()` in `tests/bootstrap.php` to prevent Patchwork timing conflicts.
- If you introduce new files that register routes at file-load time, ensure they don’t require early WP functions. Prefer registering within `register_routes()`.

## Extending
- You can extend the assertion to flag potential path-regex overlaps (two routes that could match the same URL). For now, we enforce exact `(namespace, route, method)` uniqueness.
