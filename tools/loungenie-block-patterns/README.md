# LounGenie Block Patterns

This small plugin package registers two LounGenie block patterns for Kadence: an Amenity Grid and a Pricing Table. It also includes an HTML fragment for the About page (page ID 4862) for convenience.

Installation (staging or production):

- Upload the `loungenie-block-patterns` folder into `wp-content/plugins/` on the target site (via FTP, SFTP, or the plugin uploader).
- Activate the plugin in WordPress Admin → Plugins.
- The block patterns will be available in the Block Inserter under the registered categories (loungenie, kadence, pricing).

Notes:

- This package does not push content to the database. To apply `page_4862_about.html` to page ID 4862 you can either:
  - Use the helper PowerShell REST script `tools/apply_page_4862.ps1` with an Application Password for a WP user that has editing rights; or
  - Manually edit page 4862 in Gutenberg and paste the HTML content from `page_4862_about.html` into the Code editor.

- Images referenced in the patterns should be uploaded to the Media Library at the paths used (e.g. `/wp-content/uploads/icons/qr-ordering.svg`).
