# LounGenie Portal - Filtering & Analytics Guide

Complete guide to the advanced filtering system and analytics features.

## Overview

The LounGenie Portal now includes comprehensive filtering capabilities and real-time analytics for managing and analyzing your LounGenie units across multiple dimensions.

---

## Top 5 Analytics Dashboard

Located on the Support Dashboard, the Top 5 Analytics section provides instant insights into your deployment.

### Metrics Displayed

**1. Top Colors**
- Shows the 5 most-used color tags
- Visual color indicators for each tag
- Unit count per color
- Supported colors: Yellow, Red, Classic Blue, Ice Blue

**2. Top Lock Brands**
- Distribution of MAKE vs L&F lock brands
- Unit count per brand
- Helps with inventory planning

**3. Top Venues**
- Most common venue types
- Hotel, Resort, Waterpark, Surf Park, Others
- Unit count per venue type

**4. Season Breakdown**
- Seasonal vs Year-Round units
- Total counts for each category
- Useful for seasonal planning

---

## Advanced Filtering System

The Units View provides comprehensive filtering across multiple dimensions.

### Accessing the Units View

**Support Users:**
- Navigate to "LounGenie Units" in the sidebar
- Full access to all units across all companies

**Partner Users:**
- Navigate to "My Units" in the sidebar
- See only units belonging to their company

### Available Filters

#### 1. Color Filter
Dropdown with options:
- Yellow (#FFD700)
- Red (#DC143C)
- Classic Blue (#4169E1)
- Ice Blue (#87CEEB)

**Usage:** Select a color to view only units with that color tag.

#### 2. Season Filter
Dropdown with options:
- Seasonal
- Year-Round

**Usage:** Filter units based on their operational season.

#### 3. Venue Type Filter
Dropdown with options:
- Hotel
- Resort
- Waterpark
- Surf Park
- Others

**Usage:** View units at specific venue types.

#### 4. Lock Brand Filter
Dropdown with options:
- MAKE
- L&F

**Usage:** Filter by lock manufacturer.

#### 5. Status Filter
Dropdown with options:
- Active
- Installation
- Service

**Usage:** See units in a specific operational status.

#### 6. Search Box
Free-text search across all table columns.

**Usage:** Type any text to filter rows containing that text in any field.

### Using Multiple Filters

**Simultaneous Filtering:**
- All filters work together using AND logic
- Example: Color="Yellow" AND Season="Seasonal" AND Venue="Resort"
- Results update instantly without page reload

**Active Filters Display:**
- Applied filters shown as tags below filter controls
- Each tag shows the filter type and selected value
- Click the ✕ on any tag to remove that specific filter

**Clear All Filters:**
- Click "Clear All Filters" button to reset all selections
- Table returns to showing all units

### Real-Time Updates

**Results Count:**
- Displays "X of Y units" below the table
- X = number of visible (filtered) units
- Y = total units available
- Updates instantly as filters change

**Visual Feedback:**
- Filtered rows hidden smoothly
- Count updates in real-time
- No loading delays or page reloads

---

## CSV Export Functionality

Export filtered data to CSV format for external analysis.

### How to Export

1. **Apply Filters** (optional)
   - Use any combination of filters to narrow down data
   - Only visible/filtered rows will be exported

2. **Click Export Button**
   - Located at top-right of Units table
   - Button shows "📥 Export to CSV"

3. **Wait for Processing**
   - Button changes to "⏳ Exporting..."
   - Processing takes 1-2 seconds

4. **Download Complete**
   - File downloads automatically
   - Success notification appears
   - Filename format: `loungenie-units-YYYY-MM-DD.csv`

### CSV File Format

**Columns Included:**
1. Unit ID
2. Company
3. Color
4. Season
5. Venue
6. Lock Brand
7. Status
8. Install Date

**Format Details:**
- RFC 4180 compliant CSV
- Double quotes around all values
- Proper escaping of embedded quotes
- UTF-8 encoding
- Compatible with Excel, Google Sheets, etc.

### Export Examples

**Export All Units:**
1. Clear all filters
2. Click "Export to CSV"
3. All units exported

**Export Filtered Data:**
1. Set filters: Color="Yellow", Season="Seasonal"
2. Click "Export to CSV"
3. Only matching units exported

---

## Technical Details

### Database Schema

New fields added to `wp_lgp_units` table:

```sql
lock_brand VARCHAR(50)       -- MAKE or L&F
season VARCHAR(20)           -- seasonal or year-round
venue_type VARCHAR(50)       -- Hotel, Resort, etc.
```

New field added to `wp_lgp_companies` table:

```sql
venue_type VARCHAR(50)       -- Hotel, Resort, etc.
```

### Performance

**Client-Side Filtering:**
- Filtering happens in browser (JavaScript)
- No server requests during filtering
- Instant results even with 100+ rows
- Table only queries database on page load

**Optimized Queries:**
- Database indexes on color_tag, season, venue_type, lock_brand
- Efficient SQL queries with proper JOINs
- Limited to 100 units per page for performance

### Color System

**Standard Colors:**
- **Yellow:** #FFD700 (Gold)
- **Red:** #DC143C (Crimson Red)
- **Classic Blue:** #4169E1 (Royal Blue)
- **Ice Blue:** #87CEEB (Sky Blue)

**Color Indicators:**
- Circular swatches with exact hex colors
- 16px diameter with white border
- Visible in both tables and metrics cards

---

## Use Cases

### Seasonal Planning

**Scenario:** Plan maintenance before summer season

**Steps:**
1. Filter by Season: "Seasonal"
2. Filter by Status: "Active"
3. Export to CSV
4. Schedule maintenance for all seasonal units

### Inventory Analysis

**Scenario:** Determine which lock brand to order

**Steps:**
1. View Top Lock Brands card on dashboard
2. See current distribution (e.g., MAKE: 45, L&F: 30)
3. Filter Units by Lock Brand to see specific units
4. Plan purchases based on current inventory

### Venue-Specific Reports

**Scenario:** Generate report for all resort properties

**Steps:**
1. Filter by Venue: "Resort"
2. Review filtered units
3. Export to CSV
4. Share report with resort management team

### Color Inventory Check

**Scenario:** Check availability of specific color units

**Steps:**
1. View Top Colors card to see distribution
2. Filter by specific color (e.g., "Yellow")
3. See which venues have that color
4. Export for inventory tracking

---

## Troubleshooting

### Filters Not Working

**Check:**
- JavaScript enabled in browser
- No browser console errors
- Page fully loaded before applying filters

**Solution:**
- Refresh page
- Clear browser cache
- Try different browser

### Export Returns Empty File

**Cause:** All rows filtered out

**Solution:**
- Clear filters to see all units
- Adjust filters to show desired data
- Check that units exist in database

### Color Indicators Not Showing

**Check:**
- Units have color_tag values in database
- CSS loaded properly
- Browser supports inline styles

**Solution:**
- Import sample-data.sql to populate fields
- Check browser DevTools for CSS errors

### Counts Don't Match

**Cause:** Hidden rows from filters

**Solution:**
- Results count shows visible/total
- "8 of 12 units" means 8 match filters, 12 total
- This is expected behavior

---

## Best Practices

### Filtering Workflow

1. **Start Broad:** View all units first
2. **Apply Filters:** Add filters one at a time
3. **Review Results:** Check count matches expectations
4. **Refine:** Adjust filters as needed
5. **Export:** Save filtered data if needed

### Analytics Review

1. **Check Dashboard:** Review Top 5 metrics weekly
2. **Identify Trends:** Look for unusual distributions
3. **Plan Inventory:** Use metrics for purchasing decisions
4. **Schedule Maintenance:** Use seasonal data for planning

### Data Quality

1. **Consistent Values:** Use exact values from dropdowns
2. **Update Regularly:** Keep unit data current
3. **Complete Records:** Fill in all fields for best filtering
4. **Standard Names:** Use consistent venue type naming

---

## Future Enhancements

Planned features for upcoming releases:

### Advanced Analytics
- [ ] Charts and graphs for visual data representation
- [ ] Trend analysis over time
- [ ] Predictive maintenance scheduling
- [ ] Custom date range filtering

### Enhanced Filtering
- [ ] Multi-select filters (select multiple colors at once)
- [ ] Save filter presets
- [ ] Quick filter buttons for common combinations
- [ ] Advanced search with operators (AND, OR, NOT)

### Export Options
- [ ] Excel (.xlsx) format
- [ ] PDF reports with charts
- [ ] Scheduled automated exports
- [ ] Email export to team members

### Integration
- [ ] API endpoints for filtered data
- [ ] Webhook notifications on filter changes
- [ ] Third-party BI tool integration
- [ ] Real-time sync with external systems

---

## Support

For questions or issues with filtering and analytics:

**Documentation:**
- README.md - General plugin information
- SETUP_GUIDE.md - Installation instructions
- IMPLEMENTATION_SUMMARY.md - Technical details

**Testing:**
- Import sample-data.sql for test data
- All sample units include complete field values
- Test each filter individually before combining

**Contact:**
- Check WordPress Admin → Settings → HubSpot Integration for sync status
- Review browser console for JavaScript errors
- Contact development team for custom requirements

---

**Version:** 1.0.0  
**Last Updated:** 2024-12-15  
**Compatibility:** WordPress 5.8+, PHP 7.4+
