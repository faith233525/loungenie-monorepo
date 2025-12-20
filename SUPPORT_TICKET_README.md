# Support Ticket Form System - README

## 📋 Overview

A comprehensive, production-ready support ticket submission system for the LounGenie Portal. This system enables property managers and residents to submit support requests with full form validation, file attachments, and automatic notifications.

## ✨ Features

### Core Functionality
- **Easy Submission** - Simple, intuitive form for submitting support requests
- **Form Validation** - Real-time validation with helpful error messages
- **File Attachments** - Attach up to 5 files (10MB max each) as supporting documentation
- **Auto-generated References** - Unique ticket reference for easy tracking
- **User Prefilling** - Auto-populate user information for logged-in users
- **Multi-property Support** - Handle multiple properties/companies
- **Mobile Responsive** - Works seamlessly on all devices

### Advanced Features
- **Drag-and-Drop Upload** - Easy file upload with drag-and-drop support
- **Email Notifications** - Automatic emails to requester and support team
- **Secure Storage** - Protected file storage with access controls
- **Category & Priority** - Organize tickets by type and urgency
- **Accessibility** - WCAG 2.1 Level AA compliant
- **Security** - CSRF protection, input sanitization, SQL injection prevention

## 📦 What's Included

### Implementation Files
| File | Location | Purpose |
|------|----------|---------|
| class-lgp-support-ticket-handler.php | `includes/` | Backend form processor |
| support-ticket-form.php | `templates/` | HTML form template |
| support-ticket-form.js | `assets/js/` | Validation & submission |
| support-ticket-form.css | `assets/css/` | Form styling |

### Documentation Files
| Document | Purpose |
|----------|---------|
| SUPPORT_TICKET_FORM_GUIDE.md | Complete feature documentation |
| SUPPORT_TICKET_INTEGRATION.md | Integration and configuration guide |
| SUPPORT_TICKET_USAGE_EXAMPLES.php | Code examples and patterns |
| SUPPORT_TICKET_IMPLEMENTATION_CHECKLIST.md | Deployment checklist |
| SUPPORT_TICKET_IMPLEMENTATION_SUMMARY.md | Architecture and overview |
| SUPPORT_TICKET_COMPLETE_PACKAGE.md | Package contents and quick start |

## 🚀 Quick Start

### 1. Install Files
Copy implementation files to your plugin:
```
class-lgp-support-ticket-handler.php → includes/
support-ticket-form.php → templates/
support-ticket-form.js → assets/js/
support-ticket-form.css → assets/css/
```

### 2. Initialize in Plugin
```php
// In main plugin file (wp-poolsafe-portal.php)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-support-ticket-handler.php';
LGP_Support_Ticket_Handler::init();
```

### 3. Enqueue Assets
```php
wp_enqueue_script( 'lgp-support-ticket-form' );
wp_enqueue_style( 'lgp-support-ticket-form' );
```

### 4. Add Form to Page
```php
include plugin_dir_path( __FILE__ ) . 'templates/support-ticket-form.php';
```

See **SUPPORT_TICKET_INTEGRATION.md** for detailed setup instructions.

## 📝 Form Fields

### Required Information
- **First Name** - User's first name (2+ characters)
- **Last Name** - User's last name (2+ characters)
- **Email** - Contact email (valid format required)
- **Property** - Property or company selection
- **Units Affected** - Number of units impacted
- **Category** - Type of request (maintenance, billing, etc.)
- **Urgency** - Priority level (low/medium/high/critical)
- **Subject** - Brief description (1-100 characters)
- **Description** - Detailed explanation (10+ characters)

### Optional Information
- **Phone** - Contact phone number
- **Unit IDs** - Specific affected units
- **Attachments** - Supporting files (max 5, 10MB each)

### Consent
- Contact permission checkbox
- Privacy policy agreement checkbox

## 🔒 Security Features

### Input Protection
✅ CSRF token verification (nonce validation)  
✅ Input sanitization for all fields  
✅ SQL injection prevention  
✅ XSS protection  

### File Security
✅ MIME type validation  
✅ File size limits  
✅ Filename sanitization  
✅ Protected storage directory  

### Data Protection
✅ Database integrity  
✅ Secure email transmission  
✅ User authorization checks  

## 🧪 Testing

### Validation Testing
- All required fields validated
- Character limits enforced
- Email format verified
- Phone format validated
- File types restricted
- File size limited

### Functional Testing
- Form submission successful
- Tickets created in database
- Files uploaded securely
- Confirmation email sent
- Support team notification sent
- User data prefilled correctly

### Compatibility Testing
- Desktop browsers (Chrome, Firefox, Safari, Edge)
- Mobile browsers (iOS Safari, Chrome Mobile)
- Responsive layouts
- Accessibility features

See **SUPPORT_TICKET_IMPLEMENTATION_CHECKLIST.md** for comprehensive testing guide.

## 📊 Usage Statistics

### Form Submission Flow
1. User fills out form
2. Real-time validation runs
3. Form submitted via AJAX
4. Backend validates and sanitizes
5. Ticket created in database
6. Files uploaded to secure directory
7. Confirmation email sent to user
8. Notification email sent to support team
9. Success message displayed

### Email Notifications
- **To Requester:** Ticket confirmation with reference number
- **To Support Team:** Full ticket details with action link

## 🎨 Customization

### Styling
Customize form appearance in `support-ticket-form.css`:
- Color scheme
- Layout and spacing
- Responsive breakpoints
- Button styling
- Input styling

### Categories
Edit categories in `support-ticket-form.php`:
```php
<option value="maintenance">Maintenance Issue</option>
<option value="billing">Billing Question</option>
// Add more categories
```

### Email Templates
Customize email content in `class-lgp-support-ticket-handler.php`:
- Confirmation email to requester
- Notification email to support team
- Email subject lines

### Validation Rules
Modify validation in `support-ticket-form.js`:
- Character limits
- Pattern matching
- File size limits
- File count limits

See **SUPPORT_TICKET_USAGE_EXAMPLES.php** for customization code examples.

## ⚙️ Configuration

### File Upload Settings
```php
Max file size: 10 MB (per file)
Max files: 5
Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP
```

### Urgency Levels
```
Low - 3-5 business days
Medium - 1-2 business days  
High - 24 hours
Critical - 4 hours
```

### Email Configuration
- From: Site admin email
- To: Requester email + support team email
- Reply-to: Support team email

## 📋 Database Schema

### Tickets Table
```sql
wp_lgp_tickets (
    id, company_id, user_id, requester_name,
    requester_email, requester_phone, category,
    urgency, subject, description, units_affected,
    ticket_reference, status, created_at, updated_at
)
```

### File Storage
```
Location: /wp-content/uploads/lgp-tickets/{ticket_id}/
Protection: .htaccess with deny from all
Metadata: Stored in database
```

## 🔧 Troubleshooting

### Form Not Submitting
- Check browser console for errors
- Verify nonce is valid
- Check AJAX URL configuration
- Review WordPress error logs

### Files Not Uploading
- Verify upload directory permissions (755)
- Check file size (max 10MB)
- Verify MIME type allowed
- Check PHP upload limits

### Emails Not Sending
- Verify WordPress mail configuration
- Check email address format
- Review server mail logs
- Test with WP_Mail plugin

### Validation Not Working
- Check JavaScript is enabled
- Verify script is loaded
- Check browser console errors
- Test in different browser

See **SUPPORT_TICKET_FORM_GUIDE.md** for detailed troubleshooting.

## 📚 Documentation

### For Integration
- **SUPPORT_TICKET_INTEGRATION.md** - Setup and configuration
- **SUPPORT_TICKET_IMPLEMENTATION_CHECKLIST.md** - Deployment guide
- **SUPPORT_TICKET_USAGE_EXAMPLES.php** - Code examples

### For Reference
- **SUPPORT_TICKET_FORM_GUIDE.md** - Feature documentation
- **SUPPORT_TICKET_IMPLEMENTATION_SUMMARY.md** - Architecture overview
- **SUPPORT_TICKET_COMPLETE_PACKAGE.md** - Package contents

## 🆘 Getting Help

### Documentation
All features and processes documented in detail. Start with relevant documentation file above.

### Code Examples
15+ code examples in SUPPORT_TICKET_USAGE_EXAMPLES.php covering:
- Form display
- Asset enqueuing
- Data retrieval
- Customization
- Integration patterns

### Common Issues
See "Troubleshooting" section of SUPPORT_TICKET_FORM_GUIDE.md

### Support
For issues not covered in documentation:
1. Check error logs
2. Test in WordPress debug mode
3. Verify all dependencies
4. Review code examples

## 📈 Monitoring

### Key Metrics
- Submission volume
- Form abandonment rate
- Email delivery success
- File upload frequency
- Average response time

### Maintenance Tasks
- Regular database backups
- Monitor ticket volume
- Review validation rules
- Check error logs
- Update as needed

## 🔄 Updates & Upgrades

### Version Management
- Current version: 1.0.0
- Semantic versioning used
- Backward compatibility maintained
- Database migrations documented

### Update Process
1. Backup database
2. Copy new files
3. Run database migrations
4. Test thoroughly
5. Deploy to production

## 🌟 Best Practices

### Security
- ✅ Always sanitize user input
- ✅ Verify nonces on submission
- ✅ Validate file types and sizes
- ✅ Keep WordPress updated
- ✅ Regular security audits

### Performance
- ✅ Optimize database indexes
- ✅ Minimize JavaScript/CSS
- ✅ Monitor server resources
- ✅ Archive old tickets
- ✅ Regular backups

### User Experience
- ✅ Clear error messages
- ✅ Mobile responsive
- ✅ Accessible to all users
- ✅ Fast response time
- ✅ Helpful documentation

## 📱 Responsive Design

### Breakpoints
- **Desktop** (>768px) - 2-column layout
- **Tablet** (600-768px) - 1-column layout
- **Mobile** (<600px) - Full-width layout

### Mobile Features
- Touch-friendly buttons
- Large input fields
- Simplified navigation
- Readable text
- File upload support

## ♿ Accessibility

### WCAG 2.1 Compliance
- Level AA compliant
- Keyboard navigation
- Screen reader support
- Color contrast verified
- Focus indicators visible

### Features
- Proper label associations
- Required field indicators
- Error message associations
- Help text available
- Semantic HTML structure

## 🎯 Next Steps

### For New Users
1. Read this README
2. Follow quick start guide
3. Review SUPPORT_TICKET_INTEGRATION.md
4. Copy files to your plugin
5. Test in staging environment
6. Deploy to production

### For Development
1. Review code examples
2. Understand architecture
3. Customize as needed
4. Test thoroughly
5. Document changes

### For Support Team
1. Learn form fields
2. Understand ticket references
3. Review submission notifications
4. Set up email forwarding
5. Track ticket metrics

## 📞 Contact & Support

**Documentation:** See files listed in this README  
**Code Examples:** SUPPORT_TICKET_USAGE_EXAMPLES.php  
**Issues:** Refer to troubleshooting sections  

## 📄 License

This implementation follows the same license as the LounGenie Portal plugin.

## 🙏 Credits

Developed as part of the LounGenie Portal suite.

---

**Version:** 1.0.0  
**Last Updated:** January 2024  
**Status:** Production Ready ✅

**Start your integration today using SUPPORT_TICKET_INTEGRATION.md!**
