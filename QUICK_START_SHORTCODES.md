# PoolSafe Portal - Quick Start Guide (v3.3.0)

## Shortcode Usage

### Main Portal
Place this shortcode on your portal page (e.g., `/portal`):
```
[poolsafe_portal]
```

**That's it!** This ONE shortcode renders the entire portal with:
- ✅ Role-based tab visibility (auto-detected)
- ✅ Company-centric data filtering
- ✅ CSP-compliant architecture
- ✅ SPA-style navigation

### Login Page
Place this shortcode on your login page (e.g., `/login`):
```
[poolsafe_login]
```

---

## What Users See

### Partner Users
**Role:** `pool_safe_partner`  
**Tabs Visible:** 4
- 📊 Dashboard
- 📹 Videos
- 🎫 Tickets
- ⚙️ Services

### Support/Admin Users
**Role:** `pool_safe_support` or `administrator`  
**Tabs Visible:** 5
- 📊 Dashboard
- 📹 Videos
- 🎫 Tickets
- ⚙️ Services
- 🤝 **Partners** (Support only)

---

## Key Features

### Automatic Role Detection
No configuration needed - the portal automatically detects the user's WordPress role and shows the appropriate tabs.

### Company-Centric Data
All data (tickets, services, installs, updates) is automatically filtered by the user's company, not their individual user ID.

### Fast SPA Experience
Tab switching happens instantly without page reloads for a smooth, modern experience.

### Zero CSP Violations
Built with WordPress best practices - no inline styles, scripts, or event handlers.

---

## Old Shortcodes (REMOVED)

The following shortcodes have been **deprecated and removed**:
- ❌ `[poolsafe_dashboard]`
- ❌ `[poolsafe_tickets]`
- ❌ `[poolsafe_services]`
- ❌ `[poolsafe_profile]`
- ❌ `[poolsafe_admin]`
- ❌ `[poolsafe_partners]`
- ❌ `[poolsafe_activity_log]`
- ❌ `[psp_login]`, `[psp_dashboard]`, etc.

**Migration:** Replace all legacy shortcodes with `[poolsafe_portal]`

---

## Page Setup

### Required Pages

1. **Portal Page** (e.g., `/portal`)
   - Shortcode: `[poolsafe_portal]`
   - Access: Logged-in users only
   - Recommended slug: `portal`

2. **Login Page** (e.g., `/login`)
   - Shortcode: `[poolsafe_login]`
   - Access: Public
   - Recommended slug: `login`

### Recommended Settings
- Set `/portal` as the redirect destination after login
- Use a full-width page template for best results
- Ensure users have the correct WordPress roles assigned

---

## User Roles

### pool_safe_partner
Standard partner users who manage their company's pool services.

**Assign this role to:**
- Partner company users
- Company managers
- Standard customers

### pool_safe_support
Support staff who can manage partners and view all companies.

**Assign this role to:**
- PoolSafe support team
- Customer service staff
- Account managers

### administrator
WordPress administrators with full access to everything.

**Assign this role to:**
- System administrators
- Technical staff
- Portal managers

---

## Troubleshooting

### User sees wrong tabs
✅ **Solution:** Check their WordPress user role  
1. Go to Users → All Users
2. Find the user
3. Verify their role is correct (pool_safe_partner or pool_safe_support)

### No data showing up
✅ **Solution:** Check company association  
1. User must have `psp_partner_id` or `psp_company_id` user meta
2. Company must exist in WordPress
3. Company must have associated data (tickets, services, etc.)

### CSP errors in console
✅ **Solution:** Ensure you're using v3.3.0+  
- All inline styles/scripts removed in v3.3.0
- Update to latest version if seeing CSP errors

### Tabs not switching
✅ **Solution:** Check JavaScript console for errors  
1. Open browser console (F12)
2. Look for JavaScript errors
3. Ensure `psp-portal-app.js` loaded correctly
4. Verify `PORTAL_CONFIG` is available

---

## Support

For issues or questions:
1. Check browser console for errors
2. Verify user roles are correct
3. Confirm company associations exist
4. Test with both partner and support roles

---

## Version Info

**Current Version:** v3.3.0  
**Release Date:** December 2024  
**Major Changes:**
- Consolidated to single shortcode
- Added role-based tab visibility
- Implemented company-centric data model
- Achieved 100% CSP compliance
