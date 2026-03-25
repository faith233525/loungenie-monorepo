Kadence + Gutenberg Setup (Free)

Goal: install Kadence theme and configure Gutenberg/Kadence blocks without paid plugins.

1) Install Kadence Theme (free)
   - Admin: Appearance → Themes → Add New → search for "Kadence" → Install → Activate
   - WP-CLI (if you run on server): `wp theme install kadence --activate`

2) Install Kadence Blocks (free)
   - Admin: Plugins → Add New → search "Kadence Blocks" → Install & Activate
   - WP-CLI: `wp plugin install kadence-blocks --activate`

3) Recommended free plugins (optional)
   - Classic Editor (only if needed)
   - WebP conversion plugins may be paid; prefer offline conversion prior to upload.

4) Theme JSON & styles
   - Use the repository `assets/css/lg9-site.css` and `assets/css/lg9-responsive.css` to provide site-wide styles.
   - Import these into your child theme or use the Customizer → Additional CSS.

5) Template-parts (header/footer)
   - The repo contains `artifacts/lg9_header_template_part.json` and `artifacts/lg9_footer_template_part.json`.
   - Use the `scripts/wp_import_template_part.py` helper to POST template parts via the REST API when you have an admin Application Password.

6) Mobile / Tablet checks
   - After applying template parts, test these breakpoints: 360×800, 768×1024, 1024×1366, 1440×900.
   - Verify header, navigation, and logo carousel behavior on each.

7) No-paid-plugins policy
   - Avoid Kadence Pro or paid add-ons. All blocks used in the template parts are standard Kadence and core Gutenberg blocks.

8) Troubleshooting
   - If REST imports return `rest_cannot_manage_templates`, ensure the App Password belongs to an Administrator or a user with the `edit_theme_options` and `manage_options` capabilities.

