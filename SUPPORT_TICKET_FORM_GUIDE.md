# Support Ticket Form Implementation Guide

## Overview
This document details the comprehensive support ticket form implementation for the LounGenie Portal, including required fields, validation, file handling, and user experience enhancements.

## Form Architecture

### Components
1. **Backend Handler** (`class-lgp-support-ticket-handler.php`)
   - AJAX form processing
   - Validation and sanitization
   - Database storage
   - Email notifications
   - File attachment handling

2. **Frontend Template** (`templates/support-ticket-form.php`)
   - HTML form structure
   - Responsive layout
   - User data prefilling
   - Accessibility compliance

3. **JavaScript** (`assets/js/support-ticket-form.js`)
   - Real-time validation
   - File upload handling
   - User interaction management
   - Error message display

4. **Styling** (`assets/css/support-ticket-form.css`)
   - Responsive design
   - Accessibility features
   - Print-friendly layout
   - Mobile optimization

## Required Fields

### Requester Information
| Field | Type | Validation | Required |
|-------|------|-----------|----------|
| First Name | Text | 2+ characters, letters only | Yes |
| Last Name | Text | 2+ characters, letters only | Yes |
| Email | Email | Valid email format | Yes |
| Phone | Tel | Format: (555) 123-4567 | No |

### Property Information
| Field | Type | Validation | Required |
|-------|------|-----------|----------|
| Property/Company | Select | Must exist in DB | Yes |
| Units Affected | Number | Minimum 1 | Yes |
| Unit IDs | Multi-select | Valid unit references | No |

### Issue Details
| Field | Type | Validation | Required |
|-------|------|-----------|----------|
| Category | Select | Predefined list | Yes |
| Urgency | Select | low/medium/high/critical | Yes |
| Subject | Text | 1-100 characters | Yes |
| Description | Textarea | Minimum 10 characters | Yes |

### Attachments
| Field | Type | Validation | Optional |
|-------|------|-----------|----------|
| Files | File | Max 5 files, 10MB each | Yes |

Allowed file types:
- Images: `.jpg`, `.jpeg`, `.png`, `.gif`
- Documents: `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx`, `.txt`, `.zip`

## Recommended Enhancements

### 1. Auto-Generated Ticket Reference
```
Format: TKT-YYYYMMDDHHMISS###
Example: TKT-20240115143045123
```
**Implementation:**
- Timestamp-based generation for uniqueness
- Stored with ticket for easy tracking
- Included in confirmation emails
- Used as reference in support responses

### 2. Pre-filled User Data
For logged-in users:
- First Name (read-only)
- Last Name (read-only)
- Email (read-only)
- Phone (editable)
- Company/Unit (auto-mapped)

### 3. Real-time Validation
- **Email:** RFC 5322 validation
- **Phone:** Format validation with flexibility
- **Subject:** Character count warning at 80 characters
- **Description:** Minimum length enforcement
- **Files:** Size and type validation before upload

### 4. Multi-Company Support
- Users can only select their assigned company
- Admin users can select any company
- Units dynamically loaded based on company selection
- Company ID automatically stored with ticket

### 5. Category & Urgency Selection
**Categories:**
- Maintenance Issue
- Billing Question
- Account/Access
- Feature Request
- General Inquiry
- Other

**Urgency Levels:**
- Low (3-5 business days)
- Medium (1-2 business days)
- High (24 hours)
- Critical (4 hours)

### 6. File Attachment Management
**Features:**
- Drag-and-drop upload
- Multiple file support (max 5)
- Individual file size limit (10MB)
- File preview in list
- Remove individual files
- Total size calculation

**Storage:**
- Secured directory: `/wp-content/uploads/lgp-tickets/{ticket_id}/`
- `.htaccess` protection: `deny from all`
- Stored metadata: filename, size, type, upload date

### 7. Validation with Error Messages
**Field-level validation:**
```javascript
- Name: "Please enter a valid name (2+ characters, letters only)"
- Email: "Please enter a valid email address"
- Phone: "Please enter a valid phone number"
- Subject: "Subject must be between 1 and 100 characters"
- Description: "Description must be at least 10 characters"
- Files: "File exceeds maximum size of 10MB"
```

**Form-level validation:**
- All required fields checked before submission
- Files validated for type and size
- Consent checkboxes verified

### 8. User Feedback
**Success Message:**
```
"Your support ticket has been submitted successfully.
Ticket Reference: TKT-20240115143045123"
```

**Error Messages:**
- Field-specific validation errors displayed inline
- Form-level errors shown at top with details
- File upload errors with specific file names

### 9. Auto-mapping Features
- User company auto-mapped from logged-in account
- Units dropdown populated based on company
- Requester email pre-filled from user profile
- Phone number retrieved from user metadata

### 10. Email Notifications

**Confirmation Email to Requester:**
- Ticket reference number
- Category and urgency level
- Instructions for tracking
- Support team contact info

**Notification to Support Team:**
- Full ticket details
- Requester information
- Property and units affected
- Any uploaded files listed
- Direct link to portal

## Database Schema

### Tickets Table
```sql
CREATE TABLE wp_lgp_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    user_id INT,
    requester_name VARCHAR(255) NOT NULL,
    requester_email VARCHAR(255) NOT NULL,
    requester_phone VARCHAR(20),
    category VARCHAR(50) NOT NULL,
    urgency VARCHAR(20) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    units_affected VARCHAR(255),
    ticket_reference VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX (company_id),
    INDEX (user_id),
    INDEX (ticket_reference),
    INDEX (status),
    INDEX (created_at)
);
```

### Attachments Storage
- Location: `/wp-content/uploads/lgp-tickets/{ticket_id}/`
- Metadata stored as post_meta: `_attached_files`
- Each file record includes: filename, original_name, size, type, upload date

## Security Features

### CSRF Protection
- Nonce verification: `lgp_submit_support_ticket`
- Generated and verified on every request

### Input Sanitization
- Email: `sanitize_email()`
- Text: `sanitize_text_field()`
- Textarea: `sanitize_textarea_field()`
- File uploads: Mime type validation, extension check

### File Security
- Allowed MIME types enforced
- File size limits (10MB per file, 5 files max)
- Files stored outside web root when possible
- `.htaccess` protection on upload directory
- Filenames sanitized and made unique

### Authorization
- Logged-in users can only access their company's tickets
- Admin users can access all tickets
- Company ID mapped from user account for non-admin users

## Usage Instructions

### For Users
1. Navigate to support ticket page
2. System auto-fills personal information if logged in
3. Select property and indicate units affected
4. Choose category and urgency level
5. Provide clear subject and detailed description
6. Optionally attach supporting documents
7. Agree to contact and privacy terms
8. Click "Submit Ticket"
9. Receive confirmation with ticket reference number

### For Developers
1. Enqueue scripts and styles:
   ```php
   wp_enqueue_script( 'lgp-support-ticket-form' );
   wp_enqueue_style( 'lgp-support-ticket-form' );
   ```

2. Load template:
   ```php
   include PLUGIN_DIR . '/templates/support-ticket-form.php';
   ```

3. Handler automatically processes AJAX requests
4. Check database for stored tickets
5. Review email notifications to support team

## Testing Checklist

### Form Validation
- [ ] All required fields are validated
- [ ] Error messages display inline
- [ ] Form prevents submission with errors
- [ ] Phone format validation works
- [ ] Subject character count updates
- [ ] Description character count validation

### File Upload
- [ ] Files upload successfully
- [ ] File list displays with sizes
- [ ] Individual file removal works
- [ ] File size limit enforced (10MB)
- [ ] Maximum file count enforced (5)
- [ ] Only allowed types accepted

### Data Handling
- [ ] User data pre-fills for logged-in users
- [ ] Company selection loads units
- [ ] Ticket reference auto-generates
- [ ] Nonce verification works
- [ ] Data sanitization prevents XSS

### Email Notifications
- [ ] Confirmation email sent to requester
- [ ] Notification email sent to support team
- [ ] Email contains ticket reference
- [ ] Email formatting is correct
- [ ] Links work in email client

### User Experience
- [ ] Form is responsive on mobile
- [ ] Drag-and-drop upload works
- [ ] Success message displays
- [ ] Form resets after submission
- [ ] Keyboard navigation functional
- [ ] Screen reader accessible

### Security
- [ ] CSRF token verified
- [ ] File types validated
- [ ] File sizes validated
- [ ] Input sanitization works
- [ ] SQL injection prevented
- [ ] XSS protection active

## Accessibility Features

### WCAG 2.1 Compliance
- Proper label associations for all inputs
- Required field indicators with `<span class="required">*</span>`
- Error messages linked to form fields
- Keyboard navigation fully supported
- Focus indicators visible on all interactive elements
- Color not the only means of conveying information

### Screen Reader Support
- Form landmarks using `<fieldset>` and `<legend>`
- Descriptive labels for all fields
- Help text for guidance
- Error messages announced to screen readers
- Success message announced

### Mobile Optimization
- Touch-friendly input sizes (minimum 44x44px)
- Responsive layout adapts to screen size
- File upload works on mobile devices
- Form fits within mobile viewport

## Troubleshooting

### Issue: Form not submitting
**Solution:**
1. Check nonce is valid: `wp_verify_nonce()`
2. Verify AJAX URL is correct
3. Check browser console for errors
4. Ensure form has required fields

### Issue: Files not uploading
**Solution:**
1. Check upload directory permissions
2. Verify file size limit (10MB)
3. Check MIME type is allowed
4. Look for PHP file upload errors

### Issue: Emails not sending
**Solution:**
1. Verify WordPress mail configuration
2. Check SMTP settings
3. Test with `wp_mail()` directly
4. Check email logs in database

### Issue: Form validation failing
**Solution:**
1. Check field validation regex
2. Verify data format matches expected
3. Test with browser developer tools
4. Check for JavaScript errors

## Performance Optimization

### Recommended Practices
1. **File Upload:**
   - Compress images before upload
   - Use chunked uploads for large files
   - Limit number of concurrent uploads

2. **Database:**
   - Index frequently searched fields
   - Archive old tickets regularly
   - Optimize table structure

3. **Frontend:**
   - Minify JavaScript and CSS
   - Use CSS media queries for responsive design
   - Lazy load form components if needed

## Future Enhancements

### Planned Features
1. **Ticket Status Tracking:** Real-time status updates visible to requester
2. **Comments/Replies:** Support team responses on tickets
3. **Template Responses:** Quick reply templates for common issues
4. **SLA Management:** Automatic escalation based on urgency
5. **Integration:** Connect with external support systems (Jira, Zendesk)
6. **Analytics:** Reporting on ticket volume, resolution time, satisfaction
7. **Chatbot:** AI-powered initial triage and FAQ responses
8. **Mobile App:** Native mobile app for ticket submission and tracking

## Support & Maintenance

### Regular Tasks
- Monitor ticket submission rate
- Review form analytics
- Test email delivery
- Update file type allowlist as needed
- Back up ticket data regularly
- Review and update error messages

### Version History
| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-01 | Initial implementation |

---

**For questions or issues, contact:** support@loungenie.com
