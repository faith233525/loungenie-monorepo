# New Portal Features - Quick Reference

## Feature 1: Enhanced Knowledge Center

### What's New
- Filter knowledge center guides by **type** (maintenance, repair, inspection, cleaning)
- Filter knowledge center guides by **tags** (multiple tag selection)
- Combined filtering (type + tags + search)

### API Endpoint
```
GET /lgp/v1/knowledge-center?type=maintenance&tags=filter,pool&search=guide
```
(Legacy alias `/lgp/v1/help-guides` remains for backward compatibility.)

### Example Usage (PHP)
```php
$guides = LGP_Help_Guide::get_all([
    'type' => 'maintenance',
    'tags' => ['filter', 'pool'],
    'search' => 'replacement'
]);
```

### Example Usage (JavaScript)
```javascript
fetch('/wp-json/lgp/v1/knowledge-center?type=maintenance&tags=filter,pool')
    .then(r => r.json())
    .then(guides => console.log(guides));
// Legacy alias: /wp-json/lgp/v1/help-guides
```

---

## Feature 2: Service Map View

### What's New
- **Interactive Leaflet Map** showing all service locations
- **Color-Coded Markers** by ticket urgency (Critical → Low)
- **Real-time Filtering** by urgency, status, type
- **Smart Sorting** by urgency, date, or location
- **Detail Modal** showing all tickets for a unit
- **Responsive Design** - works on desktop, tablet, mobile

### Colors
- 🔴 **Critical**: #d32f2f (Red)
- 🟠 **High**: #f57c00 (Orange)
- 🟡 **Medium**: #fbc02d (Yellow)
- 🟢 **Low**: #388e3c (Green)

### Access
- **Support Users**: See all units and tickets
- **Partner Users**: See only their company's units

### URL
Navigate to the portal and add `?view=map` to the URL:
```
https://portal.example.com/?view=map
```

### Filters Available
1. **Urgency**: Critical, High, Medium, Low
2. **Status**: Open, In Progress, Resolved, Closed
3. **Type**: Maintenance, Repair, Inspection, Cleaning
4. **Search**: By unit name or location

---

## Feature 3: Contract Status Support

### Status Types
- `active` - Current/valid contract
- `renewal_pending` - Contract expiring soon
- `expired` - Contract has ended
- `suspended` - Temporarily unavailable

### Usage in Queries
```php
// Get companies by contract status
$companies = $wpdb->get_results(
    "SELECT * FROM wp_lgp_companies WHERE contract_status = 'active'"
);
```

### Display in UI
Color-coded badges automatically appear:
- 🟢 Active (Green)
- 🟠 Renewal Pending (Orange)
- 🔴 Expired (Red)
- 🟣 Suspended (Purple)

---

## Database Schema Updates

### Required Fields for Map View

Units table needs:
```sql
ALTER TABLE wp_lgp_units ADD COLUMN latitude DECIMAL(10, 8);
ALTER TABLE wp_lgp_units ADD COLUMN longitude DECIMAL(11, 8);
ALTER TABLE wp_lgp_units ADD COLUMN location VARCHAR(255);
```

### Required Fields for Tickets

Tickets table needs:
```sql
ALTER TABLE wp_lgp_tickets ADD COLUMN urgency VARCHAR(50);
ALTER TABLE wp_lgp_tickets ADD COLUMN status VARCHAR(50);
```

---

## CSS Variables

All new components use CSS variables for easy customization:

```css
:root {
    --color-critical: #d32f2f;
    --color-high: #f57c00;
    --color-medium: #fbc02d;
    --color-low: #388e3c;
    --spacing-unit: 8px;
    --border-radius: 4px;
}
```

Override in your theme's `style.css`:
```css
:root {
    --color-critical: #your-color;
    --color-high: #your-color;
    /* ... etc ... */
}
```

---

## Testing Features

### Test Map View
1. Log in as Support User
2. Navigate to `?view=map`
3. Verify map displays with markers
4. Test filters (urgency, status, type)
5. Click a unit to see detail modal
6. Verify pagination/scrolling works
7. Test on mobile (responsive)

### Test Knowledge Center Filtering
1. Open API endpoint: `/lgp/v1/knowledge-center` (legacy alias `/lgp/v1/help-guides` still works)
2. Add `?type=maintenance` to filter
3. Add `?tags=filter,pool` to filter by tags
4. Combine: `?type=maintenance&tags=filter,pool&search=guide`

### Test Contract Status
1. Navigate to company management
2. Filter by contract_status field
3. Verify badges display with correct colors

---

## Performance Tips

### Map Performance
- Use filters to limit markers shown
- Marker clustering is automatic at zoom level > 12
- Lazy load ticket details (only fetch when modal opens)

### Database
- Add indexes on frequently filtered columns:
  ```sql
  ALTER TABLE wp_lgp_tickets ADD INDEX (unit_id);
  ALTER TABLE wp_lgp_tickets ADD INDEX (urgency);
  ALTER TABLE wp_lgp_tickets ADD INDEX (status);
  ```

### Frontend
- Minify CSS and JavaScript
- Enable browser caching for static assets
- Use CDN for Leaflet library

---

## Troubleshooting

### Map Not Loading?
```javascript
// Check in browser console (F12)
console.log(lgpMapData);  // Should show { ajaxUrl, nonce, ... }
```

### No Markers Showing?
- Check database: `SELECT * FROM wp_lgp_units WHERE latitude IS NOT NULL;`
- Verify units have latitude/longitude values

### Filters Not Working?
- Check knowledge center table (`wp_lgp_help_guides`) has `type` and `tags` columns
- Verify tags are stored as JSON: `["tag1", "tag2"]`

### Performance Issues?
- Check browser DevTools Network tab for slow requests
- Add database indexes (see Performance Tips)
- Reduce number of visible markers using filters

---

## Files Added/Modified

### New Files
- `templates/map-view.php` - Map view template
- `assets/css/map-view.css` - Map styling
- `assets/css/variables.css` - CSS variable definitions
- `assets/js/map-view.js` - Map functionality
- `tests/MapViewTest.php` - Unit tests
- `ENHANCEMENTS_SUMMARY.md` - Full documentation

### Modified Files
- `includes/class-lgp-knowledge-guide.php` (class `LGP_Help_Guide`) - Added type/tag filtering
- `api/knowledge-center.php` - Added filter parameters to API (legacy alias `/help-guides`)
- `api/units.php` - Added AJAX endpoint for map data

---

## Security Features

✅ **CSRF Protection** - AJAX requests use WordPress nonces  
✅ **Authorization** - Role-based access control  
✅ **Data Sanitization** - Input/output escaping  
✅ **SQL Injection Protection** - Using `$wpdb->prepare()`  
✅ **XSS Protection** - HTML escaping in JavaScript  

---

## Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 90+ | ✅ Full Support |
| Firefox | 88+ | ✅ Full Support |
| Safari | 14+ | ✅ Full Support |
| Edge | 90+ | ✅ Full Support |
| IE 11 | - | ❌ Not Supported |

---

## Next Steps

1. ✅ Update database schema (columns for lat/long, urgency, status)
2. ✅ Populate coordinates for existing units
3. ✅ Test all features in staging
4. ✅ Train support team on new map view
5. ✅ Deploy to production
6. ✅ Monitor performance and error logs
7. ✅ Gather user feedback for improvements

---

## Support & Questions

For issues or questions:
1. Check `ENHANCEMENTS_SUMMARY.md` for detailed docs
2. Review test cases in `tests/MapViewTest.php`
3. Check inline code comments
4. Review WordPress error logs: `wp-content/debug.log`

---

**Last Updated**: 2024-01-15  
**Version**: 1.0.0
