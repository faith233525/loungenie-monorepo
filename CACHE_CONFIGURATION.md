# Portal Caching Configuration Guide

This guide provides recommended caching rules for the LounGenie Portal to ensure dynamic content remains fresh while maximizing performance on shared hosting.

## Why Cache Exclusions Matter

The portal serves user-specific content, handles OAuth callbacks, and processes real-time data. Caching these endpoints can cause:
- Stale data shown to users
- OAuth callback failures
- Security token leaks across users
- Broken AJAX requests

## Recommended Cache Exclusions

### Critical Paths to Exclude

Always exclude these paths from page caching:

```
/portal
/portal/*
/psp-azure-callback
wp-admin/*
admin-ajax.php
```

### Query Parameters to Exclude

Exclude pages with these query parameters:

```
oauth_callback
code
state
auth
lgp_portal
lgp_section
```

### Cookies to Exclude

If user is logged in (has these cookies), do not cache:

```
wordpress_logged_in_*
wp-postpass_*
lgp_*
```

## Popular Cache Plugin Configurations

### W3 Total Cache

**Page Cache → Advanced → Never cache the following pages:**
```
/portal
/portal/*
/psp-azure-callback
wp-admin/*
admin-ajax.php
```

**Performance → Page Cache → Rejected User Agents:** (leave default)

**Performance → Page Cache → Never cache cookies:**
```
wordpress_logged_in_
wp-postpass_
lgp_
```

**Browser Cache:** Enable with 1 year expiration for CSS/JS/images

### WP Rocket

**File Optimization → Cache:**
- Enable caching for logged-in users: **OFF**

**Advanced Rules → Never Cache URLs:**
```
/portal(.*)
/psp-azure-callback
```

**Advanced Rules → Never Cache Cookies:**
```
wordpress_logged_in_
lgp_
```

**Advanced Rules → Always Purge URLs:**
```
/portal
```

### LiteSpeed Cache

**Cache → Excludes → Do Not Cache URIs:**
```
/portal
/psp-azure-callback
wp-admin
admin-ajax.php
```

**Cache → Excludes → Do Not Cache Query Strings:**
```
oauth_callback
code
state
auth
lgp_portal
lgp_section
```

**Cache → Excludes → Do Not Cache Cookies:**
```
wordpress_logged_in_
lgp_
```

**Cache → Browser → Browser Cache TTL:** 31557600 (1 year)

### WP Super Cache

**Advanced → Rejected URIs:**
```
/portal
/psp-azure-callback
wp-admin/
```

**Advanced → Rejected User Agents:** (leave default)

**Advanced → Rejected Cookies:**
```
wordpress_logged_in_
lgp_
```

## CDN Configuration (Optional)

If using a CDN (Cloudflare, StackPath, etc.):

### Static Assets to Cache (long TTL)

```
*.css
*.js
*.jpg
*.jpeg
*.png
*.gif
*.svg
*.woff
*.woff2
*.ttf
*.eot
```

### Dynamic Paths to Bypass

```
/wp-admin/*
/wp-login.php
/portal/*
/psp-azure-callback
admin-ajax.php
```

### Cloudflare Page Rules Example

1. **Rule 1 - Portal (highest priority):**
   - URL: `*yourdomain.com/portal*`
   - Settings: Cache Level = Bypass

2. **Rule 2 - OAuth Callback:**
   - URL: `*yourdomain.com/psp-azure-callback*`
   - Settings: Cache Level = Bypass

3. **Rule 3 - Static Assets:**
   - URL: `*yourdomain.com/wp-content/plugins/loungenie-portal/assets/*`
   - Settings: Cache Level = Standard, Edge Cache TTL = 1 month

## Object Cache (Recommended)

For improved database performance, enable object caching:

### Redis (best for shared hosting if available)

Install Redis Object Cache plugin:
```
wp plugin install redis-cache --activate
wp redis enable
```

### Memcached (alternative)

If Redis unavailable, use Memcached Object Cache plugin.

### Transients API

The portal already uses WordPress transients for API responses. No additional configuration needed.

## Testing Your Cache Configuration

After applying cache rules, test the following:

1. **Portal Access:**
   - Visit `/portal` as logged-in support user
   - Verify dashboard shows current data
   - Check different sections (map, tickets, training)

2. **OAuth Flow:**
   - Go to WordPress → Settings → Outlook Integration
   - Click "Authenticate with Microsoft"
   - After sign-in, verify redirect to `/psp-azure-callback` succeeds
   - Check success message and token storage

3. **AJAX Endpoints:**
   - Open browser DevTools → Network tab
   - Perform actions in portal (search, filter, submit form)
   - Verify admin-ajax.php responses are not cached (check headers)

4. **User Isolation:**
   - Log in as different users (support vs partner)
   - Verify each sees only their authorized content
   - Check no data leaks between sessions

## Performance Monitoring

After enabling caching:

- Monitor server load via hosting panel
- Check query counts (use Query Monitor plugin temporarily)
- Verify page load times (use browser DevTools or GTmetrix)
- Confirm cache hit rates in your cache plugin dashboard

## Troubleshooting

### "I see old data in the portal"

Clear the cache and add `/portal` to exclusions.

### "OAuth callback returns 404"

Ensure `/psp-azure-callback` is excluded from caching and flush WordPress permalinks.

### "Users see each other's data"

Disable caching for logged-in users or add `wordpress_logged_in_` to rejected cookies.

### "AJAX requests fail intermittently"

Exclude `admin-ajax.php` from page caching completely.

## Shared Hosting Best Practices

1. **Use Managed Caching:** Prefer your hosting provider's built-in cache (often LiteSpeed) over multiple cache plugins.

2. **Avoid Plugin Conflicts:** Don't run multiple page cache plugins simultaneously.

3. **Set Reasonable TTLs:**
   - Static assets: 1 year
   - API responses (transients): 5-15 minutes
   - Page cache: 1 hour for public pages

4. **Monitor Resource Usage:** Keep an eye on disk space (cache files) and memory usage.

5. **Schedule Cache Purges:** If your plugin supports it, purge cache after portal data updates.

## Support

If you encounter caching issues specific to your hosting environment, contact:
- Your hosting provider's support (they know their cache best)
- LounGenie Portal support with cache plugin name and version

---

**Last Updated:** December 17, 2025  
**Portal Version:** 1.6.0+
