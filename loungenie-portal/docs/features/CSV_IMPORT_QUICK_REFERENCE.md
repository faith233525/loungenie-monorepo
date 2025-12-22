# CSV Partner Import - Quick Reference

## 🚀 Quick Start

1. **Access:** WordPress Admin → LounGenie → CSV Import
2. **Upload:** Choose CSV file (max 2MB)
3. **Process:** Click "Upload and Process"
4. **Review:** Check success/error counts

---

## 📋 Required CSV Columns

```
company_name
company_email
status (active/inactive)
primary_contact_name
primary_contact_title
primary_contact_email
primary_contact_phone
```

## 📝 Optional CSV Columns

```
secondary_contact_name
secondary_contact_title
secondary_contact_email
secondary_contact_phone
```

---

## ✅ Who Can Import?

| Role | Import Access | Notes |
|------|---------------|-------|
| **Admin** | ✅ Full | Can override validations |
| **Support** | ✅ Limited | Cannot modify system settings |
| **Partner** | ❌ No | View own data only |

---

## 🔒 Security Checklist

✅ Nonce verification  
✅ Capability check: `lgp_manage_companies`  
✅ File type: .csv only  
✅ File size: 2MB max  
✅ MIME type validation  
✅ Email format validation  
✅ SQL injection prevention  
✅ XSS protection  
✅ No shell execution

---

## 📊 Import Behavior

### Create New Company
- Company email not in database
- Creates new record
- Sets status, contacts
- Logs `created` action

### Update Existing Company
- Company email matches existing
- Updates record
- Overwrites contacts
- Logs `updated` action

---

## 🧪 Dry Run Mode

✅ Preview import  
✅ Validate all rows  
✅ No database changes  
✅ See errors before import

**Enable:** Check "Dry Run" box before upload

---

## ⚠️ Common Errors

| Error | Fix |
|-------|-----|
| "Missing required columns" | Add all required columns |
| "Invalid email format" | Fix email addresses |
| "Status must be active/inactive" | Use exact values |
| "File too large" | Split into <2MB files |
| "No file uploaded" | Select CSV file first |

---

## 🔧 Validation Rules

**Company Data:**
- Name: Required
- Email: Required, valid format
- Status: active OR inactive

**Primary Contact:** All required
- Name, Title, Email, Phone

**Secondary Contact:** Optional
- Support: All or none
- Admin: Can be partial

---

## 📁 Sample CSV

```csv
company_name,company_email,status,primary_contact_name,primary_contact_title,primary_contact_email,primary_contact_phone,secondary_contact_name,secondary_contact_title,secondary_contact_email,secondary_contact_phone
"Acme Corp","contact@acme.com","active","John Smith","Manager","john@acme.com","555-0100","","","",""
```

**Download:** Use "Download Sample CSV Template" button

---

## 🎯 Best Practices

1. ✅ **Use Dry Run First**
   - Test import before committing
   - Identify errors early
   - Fix CSV before real import

2. ✅ **Batch Large Files**
   - Split >1000 rows into batches
   - Avoid timeouts on shared hosting
   - Import during off-peak hours

3. ✅ **Validate Data**
   - Check email formats
   - Verify status values
   - Complete required fields

4. ✅ **Backup Database**
   - Before large imports
   - Test on staging first
   - Keep original CSV

5. ✅ **Review Results**
   - Check success count
   - Read error messages
   - Fix and re-upload failures

---

## 🛠️ Troubleshooting

### Import Fails Completely

1. Check user role/capabilities
2. Verify file is valid CSV
3. Check file size <2MB
4. Enable WordPress debug log
5. Check browser console

### Some Rows Fail

1. Review error details table
2. Identify validation failures
3. Fix errors in CSV
4. Re-upload failed rows only

### Slow Performance

1. Split into smaller batches
2. Import during low-traffic
3. Check shared hosting limits
4. Review PHP max_execution_time

---

## 📞 Support

**Documentation:** See CSV_PARTNER_IMPORT_GUIDE.md  
**Sample CSV:** sample-partner-import.csv  
**Debug:** Enable WP_DEBUG in wp-config.php  
**Logs:** Check WordPress debug.log

---

## 🔐 WordPress.org Compliance

✅ No Composer dependencies  
✅ No external libraries  
✅ Shared hosting safe  
✅ No shell execution  
✅ Proper i18n support  
✅ All outputs escaped  
✅ All inputs sanitized

---

**Version:** 1.0.0  
**Plugin:** LounGenie Portal v1.8.1  
**Updated:** December 22, 2025
