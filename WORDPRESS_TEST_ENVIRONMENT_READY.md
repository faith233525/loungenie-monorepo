# 🎉 WordPress Test Environment - READY FOR TESTING

**Generated:** December 27, 2025  
**Status:** ✅ FULLY OPERATIONAL

---

## 🌐 Access Information

### WordPress URLs
```
Main Site:    http://localhost:8081
Admin Panel:  http://localhost:8081/wp-admin
Portal:       http://localhost:8081/portal
```

### Test Users

#### 🛠️ Support Team User
```
Username: support
Password: support123
Email:    support@loungenie.com
Role:     LounGenie Support Team
Access:   Full system access, all companies, all data
```

#### 👤 Partner Company User
```
Username: partner
Password: partner123
Email:    partner@sunsetresort.com
Role:     LounGenie Partner Company
Company:  Sunset Resort & Spa (ID: 1)
Access:   Own company data only
```

---

## 📊 Sample Data Loaded

### Management Companies: 2
1. Premium Property Management (Los Angeles, CA)
2. Sunshine Properties (Miami, FL)

### Companies: 3
1. **Sunset Resort & Spa** (Los Angeles, CA) - Resort
   - 5 units (3 Yellow, 1 Classic Blue, 1 Ice Blue)
   - 2 open tickets
   
2. **Beach Paradise Hotel** (San Francisco, CA) - Hotel
   - 2 units (1 Classic Blue, 1 Ice Blue)
   
3. **WaterWorld Park** (Miami, FL) - Waterpark
   - 2 units (1 Red, 1 Yellow)
   - 1 open ticket

### LounGenie Units: 8
**Color Distribution:**
- Yellow: 3 units
- Ice Blue: 2 units
- Classic Blue: 2 units
- Red: 1 unit

**Venue Distribution:**
- Resort: 4 units
- Hotel: 2 units
- Waterpark: 2 units

**Lock Brands:**
- MAKE: 6 units
- L&F: 2 units

**Season:**
- Year-Round: 5 units
- Seasonal: 3 units

### Support Tickets: 3
1. **Routine Maintenance Request** (Sunset Resort, pending)
2. **Urgent: Unit Not Responding** (Sunset Resort, in progress)
3. **Lock Malfunction** (WaterWorld, in progress)

---

## 🧪 Testing Checklist

### ✅ Phase 1: WordPress Core
- [x] WordPress 6.9 running
- [x] PHP 8.3.28 active
- [x] Debug mode enabled
- [x] Plugin activated
- [x] Database tables created
- [x] Sample data loaded

### 🔄 Phase 2: Authentication Tests
- [ ] Log in to wp-admin with support user
- [ ] Log in to wp-admin with partner user
- [ ] Access /portal as support user
- [ ] Access /portal as partner user
- [ ] Verify logout works
- [ ] Test invalid credentials

### 📊 Phase 3: Support Dashboard Tests
- [ ] Top 5 color analytics display
- [ ] Top 5 venue analytics display
- [ ] Lock brand analytics display
- [ ] Season distribution display
- [ ] Quick metrics cards load
- [ ] All data accurate

### 🔍 Phase 4: Units Management Tests
- [ ] Units list displays all 8 units (support view)
- [ ] Filter by color (Yellow) - should show 3 units
- [ ] Filter by venue (Resort) - should show 4 units
- [ ] Filter by lock brand (MAKE) - should show 6 units
- [ ] Filter by season (Seasonal) - should show 3 units
- [ ] Search functionality works
- [ ] Multiple filters work together
- [ ] CSV export downloads correctly
- [ ] Clear filters button works
- [ ] Filter persistence (reload page, filters remain)

### 🎫 Phase 5: Ticketing System Tests
- [ ] Tickets list shows all 3 tickets (support view)
- [ ] Ticket details display correctly
- [ ] Thread history displays
- [ ] Reply to ticket works
- [ ] Status update works
- [ ] Email notifications (if configured)
- [ ] Attachments (if any)

### 👥 Phase 6: Partner View Tests
- [ ] Partner sees only their company (Sunset Resort)
- [ ] Partner sees only their 5 units
- [ ] Partner sees only their 2 tickets
- [ ] Partner can submit service request
- [ ] Partner cannot see other companies
- [ ] Partner dashboard metrics correct

### 🔌 Phase 7: REST API Tests
```bash
# Test companies endpoint (authenticated)
curl -u support:support123 http://localhost:8081/wp-json/lgp/v1/companies

# Test units endpoint
curl -u support:support123 http://localhost:8081/wp-json/lgp/v1/units

# Test tickets endpoint
curl -u support:support123 http://localhost:8081/wp-json/lgp/v1/tickets

# Test as partner (should see limited data)
curl -u partner:partner123 http://localhost:8081/wp-json/lgp/v1/units
```

### 🗺️ Phase 8: Map View Tests
- [ ] Map loads on units page
- [ ] Markers display for units with addresses
- [ ] Clustering works (if multiple units)
- [ ] Popup shows unit details
- [ ] Map filters work with unit filters

### 📤 Phase 9: CSV Export Tests
- [ ] Export all units (8 rows)
- [ ] Export filtered units (e.g., Yellow only - 3 rows)
- [ ] CSV format correct (headers, data, encoding)
- [ ] File downloads properly
- [ ] Filename includes date

### 🔐 Phase 10: Security Tests
- [ ] Unauthenticated access to /portal redirects to login
- [ ] Partner cannot access support-only features
- [ ] SQL injection attempts fail
- [ ] XSS attempts sanitized
- [ ] Direct API access requires authentication
- [ ] CSRF tokens working

---

## 🐛 Common Issues & Solutions

### Issue: "Sorry, you are not allowed to do that"
**Cause:** Not authenticated or insufficient permissions  
**Solution:** Log in with correct user role (support or partner)

### Issue: Page shows PHP errors
**Cause:** Debug mode is enabled (expected)  
**Solution:** This is normal for testing. Check actual error message.

### Issue: No data displays on dashboard
**Cause:** Sample data not loaded correctly  
**Solution:** Check database tables have data (see verification section)

### Issue: Filters don't work
**Cause:** JavaScript not loaded or console errors  
**Solution:** Check browser console for errors, verify JS files loaded

### Issue: Map doesn't load
**Cause:** Google Maps API key or missing library  
**Solution:** Map requires configuration (optional feature)

---

## 📝 Monitoring Commands

### Watch WordPress Logs
```bash
docker logs -f local-wp-wordpress-1
```

### Watch Debug Log (real-time)
```bash
docker exec local-wp-wordpress-1 tail -f /var/www/html/wp-content/debug.log
```

### Check Database
```bash
docker exec local-wp-wordpress-1 bash -c 'cd /var/www/html && php -r "
define(\"WP_USE_THEMES\", false);
require(\"wp-load.php\");
global \$wpdb;
echo \"Companies: \" . \$wpdb->get_var(\"SELECT COUNT(*) FROM {\$wpdb->prefix}lgp_companies\") . \"\n\";
echo \"Units: \" . \$wpdb->get_var(\"SELECT COUNT(*) FROM {\$wpdb->prefix}lgp_units\") . \"\n\";
echo \"Tickets: \" . \$wpdb->get_var(\"SELECT COUNT(*) FROM {\$wpdb->prefix}lgp_tickets\") . \"\n\";
"'
```

### Verify Plugin Status
```bash
docker exec local-wp-wordpress-1 bash -c 'cd /var/www/html && php -r "
define(\"WP_USE_THEMES\", false);
require(\"wp-load.php\");
echo is_plugin_active(\"loungenie-portal/loungenie-portal.php\") ? \"ACTIVE ✓\" : \"INACTIVE ✗\";
echo \"\n\";
"'
```

### Test API Endpoints
```bash
# Companies (support user)
curl -u support:support123 http://localhost:8081/wp-json/lgp/v1/companies | jq

# Units (partner user - should see only their units)
curl -u partner:partner123 http://localhost:8081/wp-json/lgp/v1/units | jq

# Tickets
curl -u support:support123 http://localhost:8081/wp-json/lgp/v1/tickets | jq
```

---

## 🎯 Test Scenarios

### Scenario 1: Support Team Workflow
1. Log in as `support / support123`
2. Navigate to Portal
3. View Top 5 analytics
4. Go to Units page
5. Filter by "Yellow" color → Should show 3 units
6. Export to CSV → Download should contain 3 rows
7. Clear filter → Should show all 8 units
8. Go to Tickets page
9. View open tickets → Should see 3 tickets
10. Click on ticket #2 (Urgent)
11. Read thread history
12. Add reply to ticket

### Scenario 2: Partner Workflow
1. Log in as `partner / partner123`
2. Navigate to Portal
3. View dashboard → Should see only Sunset Resort data
4. Check metrics → 5 units, 2 tickets
5. Go to Units page → Should see only 5 units (not all 8)
6. Try to filter by company → Should not see dropdown (only one company)
7. Go to Tickets page → Should see only 2 tickets
8. Submit new service request
9. Verify request appears in ticket list

### Scenario 3: Filtering & Export
1. Log in as support user
2. Go to Units page (8 units total)
3. Filter: Color = "Yellow" → 3 units
4. Add filter: Season = "Year-Round" → Should narrow down
5. Export CSV → Verify correct count in file
6. Clear filters → Back to 8 units
7. Filter: Venue = "Resort" → 4 units
8. Filter: Lock Brand = "MAKE" → Should show MAKE units only
9. Use keyboard shortcut Ctrl+K → All filters cleared

---

## 📈 Performance Checks

- [ ] Dashboard loads in < 1 second
- [ ] Units page loads in < 1 second
- [ ] Filtering is instant (< 100ms)
- [ ] CSV export completes in < 2 seconds
- [ ] API responses < 300ms
- [ ] No memory leaks in browser console
- [ ] No N+1 query issues (check debug log)

---

## ✅ Success Criteria

**All features working:**
- ✅ Authentication (support + partner roles)
- ✅ Dashboard analytics (Top 5 metrics)
- ✅ Units management (list, filter, export)
- ✅ Ticketing system (list, view, reply)
- ✅ Role-based access control
- ✅ REST API endpoints
- ✅ Data persistence
- ✅ Security measures

**No critical errors:**
- ✅ No PHP fatal errors
- ✅ No JavaScript console errors
- ✅ No database errors
- ✅ No 404 on assets
- ✅ No authentication bypasses

---

## 🚀 Next Steps

1. **Start Testing:**
   - Open http://localhost:8081/wp-admin
   - Log in with support user
   - Navigate to /portal
   - Follow test scenarios above

2. **Report Issues:**
   - Note any errors in browser console
   - Check debug.log for PHP warnings
   - Document steps to reproduce

3. **Configuration (Optional):**
   - Microsoft 365 SSO (Settings → M365 SSO)
   - HubSpot CRM (Settings → HubSpot Integration)
   - Microsoft Graph Email (Settings → Outlook Integration)

---

**Test Environment Ready! Start testing at: http://localhost:8081** 🎉

**Support Login:** support / support123  
**Partner Login:** partner / partner123

---

**Generated:** December 27, 2025  
**Plugin Version:** LounGenie Portal v1.8.1  
**WordPress:** 6.9  
**PHP:** 8.3.28  
**Docker Container:** local-wp-wordpress-1
