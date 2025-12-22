# Unit/Color Tracking - Quick Reference Card

## 🎯 Core Principle

**DO NOT track individual units. Store only company-level aggregates.**

---

## 📊 Data Structure

```json
{
  "company_id": 123,
  "total_units": 15,
  "top_colors": {
    "yellow": 10,
    "orange": 5
  }
}
```

---

## 🎨 Icons Not Emojis

| Usage | Icon Class | Color |
|-------|------------|-------|
| Normal | `.lgp-urgency-normal` | 🟢 Green |
| High | `.lgp-urgency-high` | 🟡 Yellow |
| Critical | `.lgp-urgency-critical` | 🔴 Red |

**HTML:**
```html
<span class="lgp-urgency-icon lgp-urgency-normal"></span>
<span class="lgp-color-icon lgp-color-yellow"></span>
```

---

## 🔐 Role-Based Access

| Role | Visibility |
|------|-----------|
| **Support** | ALL companies |
| **Partner** | OWN company only |

---

## 💻 Quick Usage

### PHP
```php
// Display colors
lgp_render_company_colors( $company_id );

// Get aggregates
$colors = LGP_Company_Colors::get_company_colors( $company_id );
$total = LGP_Company_Colors::get_company_unit_count( $company_id );
```

### JavaScript
```javascript
fetch(`/wp-json/lgp/v1/companies/${id}`)
  .then(res => res.json())
  .then(data => {
    const colors = JSON.parse(data.top_colors || '{}');
    // Render color badges
  });
```

---

## 📝 AI Prompt

> "Do not track individual units. For each company, store only how many units exist and how many use each top color (e.g., 10 yellow, 5 orange). Display colors as aggregated counts. Use icons only, no emojis. Support sees all companies, Partners see only their own."

---

## 📚 Full Documentation

- **Technical:** `UNIT_COLOR_GUIDANCE.md`
- **Implementation:** `UNIT_COLOR_IMPLEMENTATION_SUMMARY.md`
- **Component:** `templates/components/component-company-colors.php`
