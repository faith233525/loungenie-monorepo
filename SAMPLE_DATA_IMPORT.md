# Sample Data Import Guide

This guide explains how to import sample data into your PoolSafe Portal installation for testing and demonstration purposes.

## Overview

The `sample-data.sql` file contains realistic test data including:

- **5 Companies** with login credentials
- **10 Company Contacts** (primary, secondary, and additional)
- **10 Support Tickets** with various statuses and priorities
- **6 Ticket Replies** showing conversation threads
- **10 Service Records** (completed and scheduled)
- **8 Login Log Entries** for authentication tracking
- **5 Partner Location Records** for mapping features

## Prerequisites

Before importing sample data:

1. ✅ WordPress is installed and running
2. ✅ PoolSafe Portal plugin is installed and activated
3. ✅ Database tables have been created (happens automatically on plugin activation)
4. ✅ You have database access (phpMyAdmin, MySQL command line, or similar)

## Method 1: Import via phpMyAdmin

### Step 1: Access phpMyAdmin

1. Log in to your web hosting control panel (cPanel, Plesk, etc.)
2. Click on **phpMyAdmin**
3. Select your WordPress database from the left sidebar

### Step 2: Import the SQL File

1. Click the **Import** tab at the top
2. Click **Choose File** and select `sample-data.sql`
3. Scroll down and click **Go** to start the import
4. Wait for the success message: "Import has been successfully finished"

### Step 3: Verify Import

1. Click on the database name in the left sidebar
2. Look for these tables with sample data:
   - `wp_psp_companies` - Should have 5 rows
   - `wp_psp_company_contacts` - Should have 10 rows
   - `wp_psp_tickets` - Should have 10 rows
   - `wp_psp_ticket_replies` - Should have 6 rows
   - `wp_psp_service_records` - Should have 10 rows
   - `wp_psp_login_log` - Should have 8 rows
   - `wp_psp_partners` - Should have 5 rows

## Method 2: Import via MySQL Command Line

### Step 1: Access MySQL

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to the directory containing sample-data.sql
cd /path/to/sample-data-directory
```

### Step 2: Import the File

```bash
# Import using MySQL command
mysql -u your_db_user -p your_db_name < sample-data.sql

# You'll be prompted for your database password
# Enter the password and press Enter
```

### Step 3: Verify Import

```bash
# Connect to MySQL
mysql -u your_db_user -p your_db_name

# Check row counts
SELECT COUNT(*) FROM wp_psp_companies;
SELECT COUNT(*) FROM wp_psp_tickets;
SELECT COUNT(*) FROM wp_psp_service_records;

# Exit MySQL
exit
```

## Method 3: Import via WP-CLI

If you have WP-CLI installed:

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Import the SQL file
wp db import sample-data.sql

# Verify import
wp db query "SELECT COUNT(*) FROM wp_psp_companies;"
```

## Method 4: Import via WordPress Plugin

You can use a database management plugin like:

1. **WP Database Backup** - For importing SQL files
2. **Advanced Database Cleaner** - For managing database tables
3. **phpMyAdmin** (if available as a WordPress plugin)

## Sample Company Credentials

After importing, you can log in with these test accounts:

| Company Name | Username | Password | Location |
|-------------|----------|----------|----------|
| Acme Pool Services | `acmepools` | `password123` | Los Angeles, CA |
| Clearwater Management | `clearwater` | `password123` | San Diego, CA |
| Pool Pros Inc | `poolpros` | `password123` | Miami, FL |
| Blue Wave Pool Service | `bluewavepool` | `password123` | Phoenix, AZ |
| Crystal Clear Pools | `crystalclear` | `password123` | Las Vegas, NV |

⚠️ **Security Warning**: Change these passwords before deploying to production!

## Testing Scenarios

### Scenario 1: Company Login and Ticket View

1. Navigate to your portal page: `https://your-site.com/portal`
2. Log in with username: `acmepools`, password: `password123`
3. You should see:
   - Dashboard with company statistics
   - List of tickets for Acme Pool Services
   - Service history
   - Contact information

### Scenario 2: Support User View (All Companies)

1. Log in as a support user via Microsoft 365 SSO
2. You should see:
   - Dashboard with statistics across all companies
   - All 10 tickets from all 5 companies
   - All service records
   - Company management interface

### Scenario 3: Ticket Management

1. Log in as any company
2. View open tickets
3. Click on a ticket to see replies/conversation thread
4. Test creating a new ticket
5. Test filtering by status, priority, or category

### Scenario 4: Service History

1. Log in as any company
2. Navigate to Services section
3. View completed services with costs and durations
4. View scheduled upcoming services
5. Test requesting a new service

### Scenario 5: Multi-Contact Testing

1. Log in as `poolpros` (has 3 contacts: primary, secondary, additional)
2. Verify all contacts are displayed
3. Test contact information display and formatting

### Scenario 6: Analytics Dashboard

1. Log in as support user (M365 SSO)
2. View dashboard analytics:
   - Total tickets by status (open, in_progress, pending, resolved)
   - Total companies: 5
   - Service statistics
   - Recent activity

## Data Filtering and Search

Test the portal's filtering capabilities with sample data:

### Filter Tickets by Status
- **Open**: 5 tickets
- **In Progress**: 2 tickets
- **Pending**: 2 tickets
- **Resolved**: 1 ticket

### Filter Tickets by Priority
- **High**: 3 tickets
- **Medium**: 4 tickets
- **Low**: 3 tickets

### Filter Tickets by Category
- **Maintenance**: 2 tickets
- **Service**: 2 tickets
- **Install**: 2 tickets
- **Repair**: 4 tickets

### Search by Company
- Each company has 2 tickets except Pool Pros Inc (3 tickets) and Clearwater Management (2 tickets)

## CSV Export Testing

1. Log in as support user
2. Navigate to tickets or services
3. Click **Export CSV** button
4. Verify the exported file contains:
   - All visible data based on current filters
   - Proper CSV formatting (RFC 4180 compliant)
   - Correct headers and data alignment

## Troubleshooting

### Import Failed: "Table doesn't exist"

**Problem**: Plugin tables not created

**Solution**:
1. Deactivate the PoolSafe Portal plugin
2. Reactivate the plugin (this triggers table creation)
3. Try importing again

### Import Failed: "Duplicate entry"

**Problem**: Data already exists in tables

**Solution**:
1. Clear existing sample data first:
   ```sql
   DELETE FROM wp_psp_companies WHERE username IN ('acmepools', 'clearwater', 'poolpros', 'bluewavepool', 'crystalclear');
   DELETE FROM wp_psp_company_contacts WHERE company_id IN (1, 2, 3, 4, 5);
   DELETE FROM wp_psp_tickets WHERE id BETWEEN 1 AND 10;
   DELETE FROM wp_psp_ticket_replies WHERE id BETWEEN 1 AND 6;
   DELETE FROM wp_psp_service_records WHERE id BETWEEN 1 AND 10;
   DELETE FROM wp_psp_login_log WHERE id BETWEEN 1 AND 8;
   DELETE FROM wp_psp_partners WHERE id BETWEEN 1 AND 5;
   ```
2. Import the sample data again

### Wrong Table Prefix

**Problem**: Your WordPress uses a different table prefix (not `wp_`)

**Solution**:
1. Open `sample-data.sql` in a text editor
2. Find and replace all `wp_` with your actual prefix (e.g., `wpdb_`)
3. Save the file
4. Import again

### Cannot Login with Sample Credentials

**Problem**: Passwords not working

**Solution**:
1. Verify the data was imported successfully:
   ```sql
   SELECT username, company_name FROM wp_psp_companies;
   ```
2. If needed, manually reset a password via WordPress Admin or phpMyAdmin
3. Password hash format: bcrypt (starting with `$2y$10$`)

### Data Not Showing in Portal

**Problem**: Tables populated but data doesn't display

**Solution**:
1. Clear WordPress cache (if using a caching plugin)
2. Clear browser cache and cookies
3. Check browser console for JavaScript errors
4. Verify you're logged in as the correct user type

## Clean Up Sample Data

To remove all sample data (for production deployment):

```sql
-- Delete sample companies and all related data
DELETE FROM wp_psp_companies WHERE username IN ('acmepools', 'clearwater', 'poolpros', 'bluewavepool', 'crystalclear');

-- Delete sample contacts (will cascade if foreign keys are set)
DELETE FROM wp_psp_company_contacts WHERE company_id IN (1, 2, 3, 4, 5);

-- Delete sample tickets
DELETE FROM wp_psp_tickets WHERE id <= 10;

-- Delete sample ticket replies
DELETE FROM wp_psp_ticket_replies WHERE id <= 6;

-- Delete sample service records
DELETE FROM wp_psp_service_records WHERE id <= 10;

-- Delete sample login logs
DELETE FROM wp_psp_login_log WHERE id <= 8;

-- Delete sample partners
DELETE FROM wp_psp_partners WHERE id <= 5;

-- Reset auto-increment (optional)
ALTER TABLE wp_psp_companies AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_company_contacts AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_tickets AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_ticket_replies AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_service_records AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_login_log AUTO_INCREMENT = 1;
ALTER TABLE wp_psp_partners AUTO_INCREMENT = 1;
```

## Next Steps

After successfully importing sample data:

1. ✅ Test all portal features with sample data
2. ✅ Verify filtering and search functionality
3. ✅ Test CSV export capabilities
4. ✅ Review dashboard analytics
5. ✅ Test ticket management workflow
6. ✅ Verify service history displays correctly
7. ✅ Test contact information display
8. ✅ Review login activity logs
9. ✅ Test map view with partner locations (if implemented)
10. ✅ Demonstrate portal to stakeholders using sample data

## Production Deployment

Before deploying to production:

1. ⚠️ **Remove all sample data** using the cleanup script above
2. ⚠️ **Create real company accounts** with secure passwords
3. ⚠️ **Import real customer data** if migrating from another system
4. ⚠️ **Test with real data** in a staging environment first
5. ⚠️ **Backup database** before any production deployment

## Support

For issues with sample data import:

- Check WordPress error logs
- Check MySQL error logs
- Verify database user permissions
- Review plugin activation hooks
- Contact support with specific error messages
