LiteSpeed Cache + .htaccess Checklist for WebP and caching

1. LiteSpeed Cache (WP plugin) settings
- Enable "Cache" → Cache Logged-in Users: OFF (unless needed).
- Cache Mobile: ON. Separate Mobile Cache: OFF unless mobile-specific content.
- Browser Cache: ON.
- TTLs: set reasonable values (e.g., Public : 604800 for static assets).
- Object Cache: enable only if you have Redis/Memcached and configured.
- Media Settings: Enable "Image Optimization" integration if using QUIC.cloud.
- CSS/JS Optimization: test minify/merge carefully; disable if breaks layout.
- HTTP/2 Push: enable for critical assets if server supports.

2. WebP Delivery
- Use LiteSpeed "Serve WebP" option (if available) or QUIC.cloud WebP delivery.
- If using server-side WebP conversion, ensure plugin rewrites point to .webp when Accept: image/webp.
- Ensure `Accept` negotiation is allowed; some proxies strip Accept headers.

3. .htaccess rules (place near top, before WordPress block)
- Add rules to serve .webp when present and browser supports it:

# Serve WebP if available
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_FILENAME} (.+)\.(jpe?g|png)$
RewriteCond %{DOCUMENT_ROOT}/$1.$2.webp -f
RewriteRule (.+) $1.$2.webp [T=image/webp,E=accept:1]
</IfModule>

# Correct Content-Type for .webp
<IfModule mod_mime.c>
AddType image/webp .webp
</IfModule>

- If LiteSpeed-specific directives are supported, ensure `Cache-Control` and `Expires` headers are set for static assets.

4. Cache Purge & Testing
- After enabling rules, purge LiteSpeed cache (WP Admin → LiteSpeed Cache → Toolbox → Purge)
- Test with curl to check response headers and content negotiation:

curl -I -H "Accept: image/webp" https://loungenie.com/loungenie/path/to/image.jpg

Check for: `Content-Type: image/webp`, `X-LiteSpeed-Cache: hit/miss`, and `Cache-Control`.

5. Edge cases and server
- If behind CDN, ensure CDN forwards Accept header and does not cache stale mappings.
- If using Cloudflare, enable "Origin Cache Control" or page rules to respect Cache-Control.

6. Rollback plan
- Keep a backup of `.htaccess` before changes.
- If issues, revert .htaccess and purge cache.

7. Notes specific to this site
- Sitemap and pages show .webp in many src URLs already — focus on ensuring headers match and caching TTLs are set.
- No robots.txt present — consider adding one if you want to control crawler access.

References:
- LiteSpeed docs: https://docs.litespeedtech.com/
- WebP rewrite examples: https://www.keycdn.com/support/serve-webp-images
