## Header & Footer Remediation Plan

Goal: remove duplicate navigation entries, centralize logo styles, and add a responsive logo carousel pattern for partner logos.

Steps to apply (PR-ready):

1) Inspect `artifacts/canonical_navigation_mapping.json` and confirm duplicates.

2) Export current header template-part JSON into `wp-rest-imports/header.json` (dry-run artifact). Create `wp-rest-imports/header-updated.json` with deduped `innerHTML`/`content` preserving block markup.

3) Commit the updated template-part JSON and a short note (this file) to the feature branch.

4) Add `assets/css/responsive-logo-carousel.css` (already included) and reference it from theme `_enqueue` or via a Kadence global CSS block. Example enqueue snippet:

```php
function lg_enqueue_custom_styles(){
  wp_enqueue_style('lg-responsive-logos', get_stylesheet_directory_uri() . '/assets/css/responsive-logo-carousel.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'lg_enqueue_custom_styles');
```

5) Provide a Kadence block pattern (in PR) with a `group` block containing an `lg-logo-carousel` class and nested image blocks for logos. Keep images as media references (do not inline binary data).

6) After PR review, run CI live workflow (or local `-Live`) to import `wp-rest-imports/header-updated.json` via REST, then verify on staging.

Backups: keep original exported JSON in `backups/template-parts/` in the PR for easy rollback.
