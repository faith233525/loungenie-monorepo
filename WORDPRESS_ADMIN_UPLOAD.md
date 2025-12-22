# WordPress Admin Upload Guide

## 📥 Upload via WordPress Admin Dashboard

### Step 1: Download the ZIP
**File:** `loungenie-portal-wporg-production.zip`  
**Size:** 239 KB  
**Location:** In the `deployment-artifacts/` folder

### Step 2: Go to Plugins
1. Log in to your WordPress Admin Dashboard
2. Navigate to: **Plugins** → **Add New Plugin**

### Step 3: Upload Plugin
1. Click the **"Upload Plugin"** button at the top
2. Click **"Choose File"**
3. Select: `loungenie-portal-wporg-production.zip`
4. Click **"Install Now"**

### Step 4: Activate Plugin
1. Click **"Activate Plugin"** button (appears after installation)
2. Or go to: **Plugins** → Find "LounGenie Portal" → Click **"Activate"**

### Step 5: Configure (Optional)
1. Go to: **Settings** → **LounGenie Portal** (or scroll in settings)
2. Configure integrations:
   - **Microsoft 365 SSO** (Settings → M365 SSO)
   - **HubSpot CRM** (Settings → HubSpot Integration)
   - **Email Handler** (Settings → Email Handler)

### Step 6: Access Portal
1. Navigate to: `/portal` on your website
2. Log in with your WordPress account
3. Dashboard appears based on user role:
   - **Support Team** → Full dashboard
   - **Partner Company** → Limited company view

---

## 🎯 What Happens After Upload

✅ Plugin installs to `/wp-content/plugins/loungenie-portal/`  
✅ Database tables created automatically  
✅ Plugin activated and ready to use  
✅ Access portal at `/portal`  

---

## ✨ Features Available Immediately

### After Activation
- 📊 Dashboard with analytics
- 👥 User role management (Support & Partner)
- 🗺️ Map view of locations
- 📋 Service request tracking
- 🎫 Ticket management
- 📧 Email integration (optional setup)
- 🔐 Microsoft 365 SSO (optional setup)
- 🌐 HubSpot CRM sync (optional setup)

---

## 🔧 Create User Accounts

### Create Support User
1. Go to **Users** → **Add New**
2. Username: `support_user`
3. Email: `support@yoursite.com`
4. Password: (generate strong password)
5. Role: **LounGenie Support Team** ← Important!
6. Click **"Add New User"**

### Create Partner User
1. Go to **Users** → **Add New**
2. Username: `partner_user`
3. Email: `partner@company.com`
4. Password: (generate strong password)
5. Role: **LounGenie Partner Company** ← Important!
6. Click **"Add New User"**
7. After creation, go to **Users** → Edit Partner User
8. Scroll to **LounGenie Settings**
9. Select **Company**: (choose from list)
10. Click **"Update User"**

---

## 🚀 First Steps Checklist

- [ ] Upload and activate plugin
- [ ] Create Support user (assign LounGenie Support Team role)
- [ ] Create Partner user (assign LounGenie Partner Company role)
- [ ] Test Support dashboard: `/portal` (login as support user)
- [ ] Test Partner dashboard: `/portal` (login as partner user)
- [ ] Verify database tables created: Check database
- [ ] (Optional) Configure Microsoft 365 SSO
- [ ] (Optional) Configure HubSpot integration
- [ ] (Optional) Configure email handler

---

## 📱 Testing the Plugin

### Test Support Access
1. Log out of WordPress admin
2. Go to `/portal`
3. Log in as Support user
4. Verify: See full dashboard with all companies and units

### Test Partner Access
1. Log out
2. Go to `/portal`
3. Log in as Partner user
4. Verify: See only their company's data

### Test Map View
1. Go to `/portal/map`
2. Verify: Map loads with unit locations
3. Filter by urgency/status

### Test Service Request
1. Go to `/portal/tickets`
2. Create new service request
3. Verify: Request appears in list

---

## ❓ Troubleshooting

### Plugin Won't Activate
**Error:** "Cannot activate plugin"
- Check PHP version: 7.4 or higher required
- Check WordPress version: 5.8 or higher required
- Check error logs: `/wp-content/debug.log`

**Solution:**
```bash
# Check PHP version
php -v

# Check WordPress version in admin
# Settings → General → WordPress Version
```

### Database Tables Not Created
**Problem:** Tables don't appear in database
- Deactivate and reactivate plugin
- Check database permissions
- Check MySQL error logs

**Solution:**
```bash
# Deactivate
wp plugin deactivate loungenie-portal

# Reactivate
wp plugin activate loungenie-portal
```

### Portal Not Found
**Error:** 404 on `/portal`
- Rewrite rules may not have flushed

**Solution:**
1. Go to **Settings** → **Permalinks**
2. Click **"Save Changes"** (without changing anything)
3. This flushes rewrite rules
4. Try `/portal` again

### Dashboard Shows Blank
**Problem:** Dashboard loads but shows no data
- No users assigned
- No companies/units in database

**Solution:**
1. Create sample data via admin
2. Or use included sample-data.sql file

---

## 🔐 Security Notes

✅ **HTTPS Recommended** - Use SSL certificate for OAuth  
✅ **Strong Passwords** - All user accounts need strong passwords  
✅ **Regular Backups** - Backup database and files regularly  
✅ **Keep Updated** - Keep WordPress core and plugins updated  

---

## 📞 Support Resources

**Documentation:**
- README.md - Complete overview
- PLUGIN_EXECUTIVE_SUMMARY.md - Feature guide
- DEPLOYMENT_READY.md - Installation details
- DOCUMENTATION_INDEX.md - All docs

**GitHub Repository:**
https://github.com/faith233525/Pool-Safe-Portal

---

## ✅ Verification

After activation, verify installation:

### Check via WordPress Admin
1. **Plugins** → Should show "LounGenie Portal" as Active
2. **Settings** → Should show LounGenie options
3. **Users** → Should show LounGenie roles available

### Check via Database
```sql
SHOW TABLES LIKE '%lgp_%';
```

Should show: lgp_units, lgp_companies, lgp_tickets, etc.

### Check via Browser
Navigate to `/portal` - Should redirect to login or show dashboard

---

**Status:** ✅ Ready for WordPress Admin Upload  
**File Size:** 239 KB  
**Installation Time:** 30 seconds  
**Setup Time:** 5 minutes  
