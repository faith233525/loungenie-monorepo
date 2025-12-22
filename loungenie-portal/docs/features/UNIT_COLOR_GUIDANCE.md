# Unit/Color Tracking Guidance for AI

**Last Updated:** December 19, 2025  
**Version:** 2.0

## Core Principles

### 1. Units Are NOT Tracked Individually

- **Do not create separate records or IDs for individual units**
- Only track **how many units a company has in total**
- Example: A company has **15 units total** → 10 with yellow top, 5 with orange top

### 2. Top Colors Are Company-Level Aggregates

- Store **counts per color** rather than per unit
- Example data structure:
  ```json
  {
    "top_colors": {
      "yellow": 10,
      "orange": 5
    }
  }
  ```

- UI should reflect these **aggregated counts**, not individual units
- **No unique IDs, numbers, or per-unit tracking** needed

### 3. Icons Only, No Emojis

- Use **icons** for status, badges, or any visual element
- **Do not use emojis anywhere** in the codebase
- Replace all emoji usage with appropriate icon components or CSS classes

### 4. Role-Based Display

| Role | Visibility |
|------|-----------|
| **Support** | Sees **all companies** with their unit counts and color aggregates |
| **Partner** | Sees **only their own company**, showing total units and color distribution |

## Database Schema

### Companies Table (`lgp_companies`)

```sql
CREATE TABLE lgp_companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  -- ... other fields ...
  top_colors JSON DEFAULT NULL,  -- Stores color aggregates
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**`top_colors` JSON structure:**
```json
{
  "yellow": 10,
  "orange": 5,
  "red": 2,
  "green": 8
}
```

### Units Table (`lgp_units`)

Units table exists **only for internal tracking and maintenance**, not for customer-facing features:

- Used by Support for service history and maintenance schedules
- **Never expose individual unit IDs to Partners**
- Aggregate by `color_tag` column to populate `companies.top_colors`

## API Endpoints

### Get Company Color Aggregates

**Endpoint:** `GET /wp-json/lgp/v1/companies/{id}`

**Response:**
```json
{
  "id": 123,
  "name": "Acme Pool Services",
  "total_units": 15,
  "top_colors": {
    "yellow": 10,
    "orange": 5
  }
}
```

### Dashboard Metrics

**Endpoint:** `GET /wp-json/lgp/v1/dashboard`

**Support Response:**
```json
{
  "role": "support",
  "total_units": 450,
  "companies": [
    {
      "id": 1,
      "name": "Company A",
      "unit_count": 15,
      "top_colors": {"yellow": 10, "orange": 5}
    },
    {
      "id": 2,
      "name": "Company B",
      "unit_count": 22,
      "top_colors": {"yellow": 12, "green": 8, "red": 2}
    }
  ]
}
```

**Partner Response:**
```json
{
  "role": "partner",
  "company_id": 1,
  "company_name": "Company A",
  "total_units": 15,
  "top_colors": {
    "yellow": 10,
    "orange": 5
  }
}
```

## UI Implementation

### Color Display Components

```php
// Display aggregated color counts
foreach ($top_colors as $color => $count) {
    echo '<div class="lgp-color-badge">';
    echo '<span class="lgp-color-icon lgp-color-' . esc_attr($color) . '"></span>';
    echo '<span class="lgp-color-count">' . absint($count) . '</span>';
    echo '</div>';
}
```

### CSS Icon Classes (NOT Emojis)

```css
.lgp-color-icon {
  display: inline-block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  margin-right: 4px;
}

.lgp-color-icon.lgp-color-yellow { background: #FFC107; }
.lgp-color-icon.lgp-color-orange { background: #FF9800; }
.lgp-color-icon.lgp-color-red { background: #F44336; }
.lgp-color-icon.lgp-color-green { background: #4CAF50; }
```

### Status Icons (NOT Emojis)

Replace all emoji usage:

| Old (Emoji) | New (Icon Class) | Description |
|------------|------------------|-------------|
| 🔴 | `<span class="lgp-icon-critical"></span>` | Critical/Emergency |
| 🟡 | `<span class="lgp-icon-high"></span>` | High Priority |
| 🟢 | `<span class="lgp-icon-normal"></span>` | Normal Priority |

## Code Examples

### Fetching Company Colors (Backend)

```php
// Use LGP_Company_Colors utility class
$company_id = 123;
$colors = LGP_Company_Colors::get_company_colors($company_id);
$total_units = LGP_Company_Colors::get_company_unit_count($company_id);

// Result:
// $colors = ['yellow' => 10, 'orange' => 5]
// $total_units = 15
```

### Updating Color Aggregates

```php
// Automatically handled by hooks when units change
// Manual refresh if needed:
LGP_Company_Colors::refresh_company_colors($company_id);
```

### Frontend Display (JavaScript)

```javascript
// Fetch company data
fetch('/wp-json/lgp/v1/companies/123')
  .then(res => res.json())
  .then(data => {
    const colors = data.top_colors;
    const total = data.total_units;
    
    // Display aggregated counts
    Object.entries(colors).forEach(([color, count]) => {
      renderColorBadge(color, count);
    });
  });
```

## Migration & Maintenance

### Initial Population

When adding the `top_colors` column, run:

```php
LGP_Company_Colors::batch_refresh(); // Refreshes all companies
```

### Cache Invalidation

Color aggregates are cached for 1 hour. Invalidation happens automatically on:
- Unit creation
- Unit update (color_tag change)
- Unit deletion

### Manual Cache Clear

```php
wp_cache_delete("company_colors_{$company_id}", 'loungenie_portal');
wp_cache_delete("company_unit_count_{$company_id}", 'loungenie_portal');
```

## AI Prompt Summary

**For AI Assistants:**

> Do not track individual units. For each company, store only:
> 1. Total unit count (integer)
> 2. Color distribution as aggregated counts (JSON: `{"yellow": 10, "orange": 5}`)
>
> Display colors as aggregated counts in dashboards, tickets, or maps.
>
> Use **icons only**, no emojis anywhere.
>
> Respect role-based visibility:
> - Support sees **all companies**
> - Partners see **only their own company**

## Files Updated

- `/loungenie-portal/includes/class-lgp-company-colors.php` - Color aggregation utility
- `/loungenie-portal/includes/class-lgp-migrations.php` - Database migration (v1.8.0)
- `/loungenie-portal/api/dashboard.php` - Role-based dashboard metrics
- `/loungenie-portal/api/companies.php` - Company API with color data
- `/loungenie-portal/templates/components/support-ticket-form.php` - Updated UI (no emojis)

## Testing

### Verify Color Aggregates
```bash
# Check company colors
wp eval "var_dump(LGP_Company_Colors::get_company_colors(1));"

# Check total units
wp eval "var_dump(LGP_Company_Colors::get_company_unit_count(1));"
```

### Test Role-Based Access
```bash
# As Support (sees all)
curl -X GET "https://portal.example.com/wp-json/lgp/v1/companies" \
  -H "Authorization: Bearer SUPPORT_TOKEN"

# As Partner (sees own only)
curl -X GET "https://portal.example.com/wp-json/lgp/v1/companies/123" \
  -H "Authorization: Bearer PARTNER_TOKEN"
```

---

**Remember:** Units are aggregated at the company level. No individual unit tracking in UI/API for Partners.
