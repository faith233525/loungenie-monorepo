# LounGenie Portal - Setup Guide

Complete guide to getting started with LounGenie Portal.

## Installation

### Step 1: Install Plugin

1. Upload the `loungenie-portal` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "LounGenie Portal" and click **Activate**

The plugin will automatically:
- Create database tables
- Register custom roles (Support & Partner)
- Set up rewrite rules for `/portal` route

### Step 2: Verify Installation

Visit your site at `/portal`. You should be redirected to the WordPress login page (since you're not authenticated yet).

## Creating Users

### Creating Support Users

Support users have full access to all companies, units, tickets, and the map view.

**Via WordPress Admin:**

1. Go to **Users → Add New**
2. Fill in user details:
   - Username: `support-john` (example)
   - Email: `john@loungenie.com`
   - Password: Set a strong password
3. Under **Role**, select **LounGenie Support**
4. Click **Add New User**

**Via Code (wp-cli or custom script):**

```php
$user_id = wp_create_user( 'support-john', 'password123', 'john@loungenie.com' );
$user = new WP_User( $user_id );
$user->set_role( 'lgp_support' );
```

### Creating Partner Users

Partner users have limited access to only their company's data.

**Via WordPress Admin:**

1. Go to **Users → Add New**
2. Fill in user details:
   - Username: `partner-acme`
   - Email: `contact@acme.com`
   - Password: Set password
3. Under **Role**, select **LounGenie Partner**
4. Click **Add New User**
5. **Important:** You must link this user to a company (see below)

**Linking Partner to Company:**

After creating the partner user, you need to associate them with a company:

```php
// Get the user ID and company ID
$user_id = 123; // The partner user ID
$company_id = 456; // The company ID from lgp_companies table

// Link user to company
update_user_meta( $user_id, 'lgp_company_id', $company_id );
```

## Adding Data

### Adding Companies

Companies can be added via REST API or directly in the database.

**Via REST API (requires Support authentication):**

```bash
curl -X POST "https://yoursite.com/wp-json/lgp/v1/companies" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  -d '{
    "name": "Acme Corporation",
    "address": "123 Main St, Suite 100, City, State 12345",
    "state": "CA",
    "contact_name": "John Doe",
    "contact_email": "john@acme.com",
    "contact_phone": "(555) 123-4567",
    "management_company_id": 0
  }'
```

**Via SQL (direct database):**

```sql
INSERT INTO wp_lgp_companies (name, address, state, contact_name, contact_email, contact_phone)
VALUES ('Acme Corporation', '123 Main St, Suite 100', 'CA', 'John Doe', 'john@acme.com', '(555) 123-4567');
```

### Adding Management Companies

Management companies are companies that manage multiple properties.

```sql
INSERT INTO wp_lgp_management_companies (name, address, contact_name, contact_email)
VALUES ('Premium Property Management', '456 Oak Ave', 'Jane Smith', 'jane@ppm.com');
```

Then update companies to reference this management company:

```sql
UPDATE wp_lgp_companies SET management_company_id = 1 WHERE id IN (2, 3, 4);
```

### Adding LounGenie Units

Units belong to companies and can have management company associations.

**Via REST API (Support only):**

```bash
curl -X POST "https://yoursite.com/wp-json/lgp/v1/units" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  -d '{
    "company_id": 1,
    "management_company_id": 1,
    "address": "Pool A, 123 Main St",
    "lock_type": "Smart Lock Pro",
    "color_tag": "Blue",
    "status": "active",
    "install_date": "2024-01-15"
  }'
```

**Via SQL:**

```sql
INSERT INTO wp_lgp_units (company_id, management_company_id, address, lock_type, color_tag, status, install_date)
VALUES (1, 1, 'Pool A, 123 Main St', 'Smart Lock Pro', 'Blue', 'active', '2024-01-15');
```

## Testing the Portal

### Test Support Access

1. **Log out** of WordPress if you're logged in
2. Navigate to `/portal`
3. You'll be redirected to login
4. Log in with support credentials
5. After login, you'll be redirected back to `/portal`
6. You should see:
   - Support Dashboard with statistics
   - Full navigation menu (Companies, Units, Tickets, Map View)
   - Access to all data

### Test Partner Access

1. **Log out**
2. Navigate to `/portal`
3. Log in with partner credentials
4. You should see:
   - Partner Dashboard with company info
   - Limited navigation (Dashboard, My Units, Service Requests)
   - Service request submission form
   - Only data related to their company

### Test Service Request Submission (Partner)

1. Log in as partner
2. Go to Dashboard
3. Fill out the service request form:
   - Request Type: Select from dropdown
   - Priority: Normal/High/Urgent
   - Notes: Describe the issue
4. Click **Submit Request**
5. You should see a success notification
6. The request appears in "Recent Activity" table

### Test REST API Endpoints

Get companies (as Support):

```bash
curl "https://yoursite.com/wp-json/lgp/v1/companies" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE"
```

Get units (filtered by role):

```bash
curl "https://yoursite.com/wp-json/lgp/v1/units?page=1&per_page=20" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE"
```

Create ticket (as Partner):

```bash
curl -X POST "https://yoursite.com/wp-json/lgp/v1/tickets" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  -d '{
    "request_type": "maintenance",
    "priority": "normal",
    "notes": "Pool heater not working properly",
    "unit_id": 1
  }'
```

## Common Issues

### Issue: "Access Denied" when accessing /portal

**Solution:** Ensure the user has either `lgp_support` or `lgp_partner` role.

### Issue: Partner can't see any units

**Solution:** 
1. Verify the partner user has `lgp_company_id` user meta set
2. Verify units exist in database with matching `company_id`

### Issue: /portal returns 404

**Solution:**
1. Go to WordPress Admin → Settings → Permalinks
2. Click **Save Changes** (this flushes rewrite rules)
3. Try accessing `/portal` again

### Issue: CSS not loading or looks broken

**Solution:**
- Clear browser cache
- Check that `loungenie-portal/assets/css/portal.css` exists
- Verify plugin is activated

## Advanced Configuration

### Customizing the Design

The design system uses CSS variables defined in `assets/css/portal.css`. To customize colors:

```css
:root {
    --primary: #3AA6B9;      /* Change primary color */
    --secondary: #25D0EE;    /* Change secondary color */
    --accent: #C8A75A;       /* Change accent color */
}
```

### Adding Map Integration

The map view currently shows a placeholder. To integrate with a mapping service:

1. Get API key from Google Maps, Mapbox, or OpenStreetMap
2. Edit `templates/map-view.php`
3. Replace the placeholder div with map initialization code
4. Use company addresses to plot markers

Example with Google Maps:

```javascript
// In templates/map-view.php
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
<script>
function initMap() {
    const map = new google.maps.Map(document.getElementById('lgp-map-container'), {
        zoom: 6,
        center: {lat: 37.0902, lng: -95.7129}
    });
    
    // Add markers for each company
    <?php foreach ($companies as $company) : ?>
    // Geocode address and add marker
    <?php endforeach; ?>
}
</script>
```

### Email Notifications

To send email notifications when service requests are created:

Add to `api/tickets.php` after ticket creation:

```php
// Send email notification
$to = get_option('admin_email');
$subject = 'New Service Request #' . $service_request_id;
$message = sprintf(
    'A new service request has been submitted.\n\nType: %s\nPriority: %s\nNotes: %s',
    $request_data['request_type'],
    $request_data['priority'],
    $request_data['notes']
);

wp_mail($to, $subject, $message);
```

## Next Steps

1. **Add sample data** - Create companies, units, and test service requests
2. **Customize branding** - Update colors in CSS variables
3. **Set up email notifications** - Configure SMTP for ticket updates
4. **Integrate mapping** - Add Google Maps or alternative
5. **Configure backups** - Regular database backups for company data
6. **Train users** - Create documentation for support and partner users

## Support

For technical support or questions about LounGenie Portal, contact the development team.
