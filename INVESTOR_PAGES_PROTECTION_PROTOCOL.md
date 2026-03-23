# INVESTOR PAGES PROTECTION PROTOCOL

**Status**: ACTIVE  
**Protected Pages**: Investors | Financials | Board | Press  
**Last Updated**: March 21, 2026  
**Backup Location**: `c:\Users\pools\Documents\wordpress-develop\`

---

## Protected Pages & IDs

| Page | ID | Backup File | Backup Size |
|------|----|----|---|
| Investors | 5668 | `investors_protected_backup.html` | 27.9 KB |
| Financials | 5686 | `financials_protected_backup.html` | 27.5 KB |
| Board | 5651 | `board_protected_backup.html` | 27.3 KB |
| Press | 5716 | `press_protected_backup.html` | 35.6 KB |

---

## Protection Layers

### Layer 1: OFFLINE BACKUPS ✓
- **Status**: All 4 pages backed up locally
- **Location**: Individual `.html` files saved to disk
- **Update Frequency**: After any protection scripts run
- **Restore Time**: < 5 seconds per page

### Layer 2: WORDPRESS REVISIONS ✓
- **Status**: WordPress keeps automatic revision history
- **Depth**: 20+ revisions per page
- **Last Snapshot**: March 21, 2026 (current protection date)
- **Restore Via**: WP Admin > Pages > [page name] > Revisions > Restore

### Layer 3: PROTECTION METADATA ✓
- **Status**: Protection flags set on all pages
- **Field**: `_page_protected` = 'yes'
- **Field**: `_page_protection_date` = '2026-03-21'
- **Effect**: Marks pages as officially locked (visual indicator in admin)

### Layer 4: MONITORING (OPTIONAL but RECOMMENDED) 
- **Status**: Monitoring script available
- **Command**: `python check_investor_pages.py`
- **Frequency**: Weekly (recommend scheduling)
- **Alert**: Detects unauthorized changes via content hash

---

## How to RESTORE a Page (If Changed)

### Quick Restore (1 command):
```powershell
python restore_investor_pages.py press    # Restore press page
python restore_investor_pages.py all      # Restore all 4 pages
```

### Manual Restore via WordPress Admin:
1. Log in: https://www.loungenie.com/wp-admin/
2. Pages > [Page Name]
3. Click "Revisions" (top right corner)
4. Select date you want: "March 21, 2026"
5. Click "Restore This Revision"
6. Done ✓

### Restore from Local Backup:
1. Open: `{page_name}_protected_backup.html`
2. Copy all content EXCEPT header comments
3. WordPress admin > Page > Paste > Update
4. Clear cache: Ctrl+Shift+R

---

## RULES FOR PROTECTED PAGES

❌ **DO NOT:**
- Edit these pages in the WordPress admin without explicit approval
- Delete any backup files
- Modify page titles or slug URLs
- Change page status to Draft/Private/Trash

✓ **YOU CAN:**
- View the pages publicly
- Link to them in other pages
- Update cache
- Monitor them with the check script

⚠️ **IF CHANGES NEEDED:**
1. Message admin before editing
2. Create a new revision (WordPress does this automatically)
3. Document the change
4. Run `python check_investor_pages.py` to verify integrity

---

## AUTOMATED MONITORING (Setup Instructions)

### Windows Task Scheduler:
```
1. Open: Task Scheduler (search "task scheduler")
2. Create Basic Task > "Monitor Investor Pages"
3. Trigger: Weekly > Monday 9:00 AM
4. Action: Start Program
   - Program: C:\Python313\python.exe
   - Arguments: check_investor_pages.py
   - Start in: c:\Users\pools\Documents\wordpress-develop
5. Conditions: Run whether user is logged in or not
6. Click Create
```

### Linux/Mac (cron):
```bash
# Add to crontab: crontab -e
0 9 * * 1 cd /path/to/wordpress-develop && python check_investor_pages.py

# This runs every Monday at 9 AM
```

---

## EMERGENCY PROCEDURES

### If Page Accidentally Deleted:
1. **DO NOT PANIC** — All backups exist
2. Run: `python restore_investor_pages.py {page_name}`
3. Page restored in < 5 seconds
4. Clear browser cache: Ctrl+Shift+R

### If Multiple Pages Changed:
1. Run: `python check_investor_pages.py` (detects changes)
2. Run: `python restore_investor_pages.py all` (restores all)
3. Investigate WHO changed them via WordPress edit logs

### If Backups Corrupted:
1. Use WordPress Revisions > Restore to any past date
2. Get revision history: `python check_investor_history.py`
3. Contact system admin

---

## VERIFICATION CHECKLIST

Run these commands to verify protection status:

```powershell
# Check all backups exist
Get-Item *_protected_backup.html

# Verify page integrity
python check_investor_pages.py

# View revision history
python check_investor_history.py

# View current page content state
python -c "import urllib.request, json, base64; auth='i6IM cqLZ vQDC pIRk nKFr g35i'; credentials=base64.b64encode(b'admin:'+auth.encode()).decode(); url='https://www.loungenie.com/wp-json/wp/v2/pages'; req=urllib.request.Request(url, headers={'Authorization':'Basic '+credentials}); r=urllib.request.urlopen(req); data=json.loads(r.read()); print('\n'.join([f\"{p['id']}: {p['title']['rendered']}\".strip() for p in data if p['id'] in [5668,5686,5651,5716]]))"
```

---

## IMPORTANT NOTES

⚠️ **WordPress Admin Only**  
- Only admins can access the protected page status
- Editing restrictions require additional plugin (not active by default)
- These pages CAN still be edited — protection is informational + backup-based

⚠️ **Backup Maintenance**  
- Keep backup files in a safe, accessible location
- Consider copying to external drive quarterly
- Do NOT delete backup files

✓ **Best Practice**  
- Schedule weekly monitoring via Task Scheduler
- Document any intentional changes with dates/reasons
- Brief team on this protection protocol
- Review revisions monthly

---

## Quick Reference

**Protected Pages**: investorsfinancials | board | press  
**Backup Command**: Already done ✓  
**Check Status**: `python check_investor_pages.py`  
**Restore One**: `python restore_investor_pages.py {page_name}`  
**Restore All**: `python restore_investor_pages.py all`  

---

**Document Version**: 1.0  
**Created**: March 21, 2026  
**By**: Copilot (github.com/copilot)
