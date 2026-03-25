Implementing the site with Kadence + Gutenberg (practical steps)
===============================================================

Goal: have all content and images editable inside WordPress (Gutenberg/Kadence) while keeping custom patterns, PHP snippets, and CSS manageable via plugins.

1) Install plugins (staging first)
- Kadence Blocks & Kadence Theme
- Kadence AI / Kadence Design Library (if available)
- WPIDE or File Manager (for in-dashboard file edits)
- AI Engine (dashboard AI assistant)
- LiteSpeed Cache
- Rank Math (SEO)

2) Register block patterns
- Copy `tools/register_loungenie_block_patterns.php` into your `loungenie-block-patterns` plugin (place in plugin root or include from main plugin file). Activate the plugin. The patterns will appear in the block inserter under ‘Patterns’. Use them to build pages visually.

3) Populate Pages
- Use `tools/page_4862_about.html` as the content for page ID 4862 — open the page in Gutenberg, switch to Code Editor, paste the HTML/blocks, then switch back to visual editor to tweak blocks.
- For other pages, use the Kadence block patterns (Amenity Grid / Pricing Table) and customize text/images.

4) Images & Media
- Upload images referenced in `tools/image_inventory.csv` to Media Library → uploads/YYYY/MM/.
- Replace the image src in block patterns with the Media Library URLs (use the image block UI to select uploaded files).

5) Header/Footer
- Prefer using Kadence Visual Header/Footer builder for menus and logo. For custom scripts or filters, add small snippets to `LounGenie Theme Brain` plugin instead of theme `functions.php` to keep changes portable.

6) Styling & Custom CSS
- Add site-level CSS in Appearance → Customize → Additional CSS or create a small plugin (e.g., `loungenie-custom-css`) with an enqueued stylesheet. Keep CSS limited to component classes used by Kadence blocks.

7) Testing
- Clear LiteSpeed cache and test pages on mobile and desktop. Use PageSpeed Insights and address top 3 issues (images, render-blocking, unused CSS).

8) Deploy to production
- Backup production first; test on staging thoroughly; use same plugins and PHP snippets; consider switching automation to push via Pabbly only after human review.
