# LounGenie Portal - HTML Demo Suite

A comprehensive standalone HTML demo of the LounGenie WordPress plugin portals for design validation before WordPress integration.

## Overview

This demo suite includes fully functional, responsive HTML implementations of:
- **Login Page** - Role-based authentication (Support/Partner)
- **Support Portal** - Company management, maps, CSV import, support tickets, videos
- **Partner Portal** - Company profiles, support tickets, documents, settings

## Features Demonstrated

### Login Page (01-login-page-demo.html)
- ✅ Role selector (Partner/Support radio buttons)
- ✅ Email/password login with demo credentials
- ✅ SSO integration (Microsoft 365, Google Workspace)
- ✅ Session storage for role/email persistence
- ✅ Responsive design (mobile-first)
- ✅ Brand color scheme (teal, sky, sea, navy)

### Support Portal (02-support-portal-demo.html)
- ✅ Dashboard with KPI statistics
- ✅ Company management (cards with edit options)
- ✅ Interactive map visualization (Leaflet.js)
- ✅ CSV file upload with preview
- ✅ Data import functionality
- ✅ Video tutorials (embedded YouTube players)
- ✅ Support ticket management
- ✅ Collapsible sidebar navigation
- ✅ User session management

### Partner Portal (03-partner-portal-demo.html)
- ✅ Dashboard with quick actions
- ✅ Company profile management (editable)
- ✅ Collapsible company details
- ✅ Support ticket creation/viewing
- ✅ Document downloads (6 document types)
- ✅ Account settings (notifications, preferences)
- ✅ Profile information (read/edit)
- ✅ Responsive layout
- ✅ User session management

## Getting Started

### 1. Open Login Page
Open `01-login-page-demo.html` in your web browser.

### 2. Login
- **For Support Portal:**
  - Select "Support" role
  - Click "Login" (credentials pre-filled)
  
- **For Partner Portal:**
  - Select "Partner" role
  - Click "Login" (credentials pre-filled)

### 3. Demo Credentials
- **Email:** partner@poolsafe.com
- **Password:** demo123
- Both roles use same credentials (for demo purposes)

## Demo Walkthrough

### Support Portal Features to Test

1. **Dashboard** - View KPI cards and recent tickets
2. **Companies** - Browse company cards with status badges
3. **Map View** - Interactive map showing company locations (California, Texas, Florida)
4. **CSV Import** - Upload CSV file with company data
   - Click upload zone or drag & drop
   - Preview data before import
   - Download template for correct format
5. **Videos** - Watch embedded training videos
6. **Tickets** - View and create support tickets

### Partner Portal Features to Test

1. **Dashboard** - Quick action buttons and statistics
2. **My Profile** - View/edit personal information
3. **My Companies** - Collapsible sections for multiple companies
4. **Tickets** - View existing and create new support tickets
5. **Documents** - Download 6 resource types (PDF, CSV, MP4, etc.)
6. **Settings** - Configure notifications and preferences

## Design Verification

All elements use the unified 4-color brand palette:
- **Primary (Teal):** #1CAAB6 - Main buttons, actions
- **Secondary (Sky):** #67BED9 - Hover states, secondary elements
- **Success (Sea):** #4DA88F - Success badges, confirmations
- **Structure (Navy):** #1A237E - Text, navigation, structure

### Responsive Breakpoints
- **Desktop:** Full sidebar (250px fixed)
- **Tablet:** All features accessible
- **Mobile (< 768px):** Responsive sidebar, stack layouts

## Testing Checklist

### Functionality
- [ ] Login redirects to correct portal based on role
- [ ] Session persists across page navigation
- [ ] All navigation links work
- [ ] Forms accept input (no backend submission needed)
- [ ] CSV preview shows data correctly
- [ ] Map displays with company markers
- [ ] Videos embed and play
- [ ] Collapsible sections open/close

### Design
- [ ] Brand colors applied consistently
- [ ] Typography (Montserrat font) renders correctly
- [ ] Spacing and alignment look professional
- [ ] Buttons have hover states
- [ ] Modals display with backdrop
- [ ] Responsive design works on mobile

### User Experience
- [ ] Navigation is intuitive
- [ ] Buttons are clearly clickable
- [ ] Form validation provides feedback
- [ ] No console errors (open DevTools F12)
- [ ] Page loads without external dependencies issues

## File Structure

```
demos/
├── 01-login-page-demo.html      # Main login portal
├── 02-support-portal-demo.html  # Support team dashboard
├── 03-partner-portal-demo.html  # Partner company dashboard
└── README.md                    # This file
```

## Technical Details

### Dependencies
- **Fonts:** Google Fonts (Montserrat)
- **Icons:** FontAwesome 6.5.1 CDN
- **Maps:** Leaflet.js 1.9.4 (Support Portal)
- **All CSS/JS:** Inline (no external dependencies except CDN resources)

### Browser Support
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

### Storage
- Uses `sessionStorage` for user role and email
- Clears on browser close for security
- Logout button clears session immediately

## Known Limitations

These are **design/demo-only** files:
- ❌ No backend API integration
- ❌ No actual data persistence
- ❌ No real email sending
- ❌ No file storage (CSV imports stay in memory)
- ❌ Forms don't submit (for demo feedback only)

All functionality is simulated for design/UX verification purposes.

## Next Steps

### Before WordPress Integration
1. Review all pages for design approval
2. Test all interactions in different browsers
3. Verify responsive design on mobile
4. Check for any design issues or improvements
5. Get stakeholder sign-off on UI/UX

### WordPress Integration Checklist
- [ ] Copy CSS from `portal.css` → WordPress plugin
- [ ] Copy JavaScript from `.js` files → WordPress plugin
- [ ] Create WordPress templates from HTML sections
- [ ] Connect to actual plugin backend APIs
- [ ] Implement real authentication
- [ ] Add database persistence
- [ ] Configure role-based access controls
- [ ] Test with actual WordPress environment

## Support

For design feedback or feature requests:
1. Note specific page/section with feedback
2. Document exact issue (screenshot helpful)
3. Specify browser/device tested on
4. Provide requested changes or suggestions

## Version

**Demo Suite v1.0.0**
- Created: 2024
- Purpose: Pre-WordPress design validation
- Status: Ready for visual/functional review
