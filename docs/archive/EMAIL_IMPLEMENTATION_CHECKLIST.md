# Email Integration Implementation Checklist

## Phase 1: Setup and Configuration

### Azure AD Configuration
- [ ] Create/identify shared mailbox in Microsoft 365
- [ ] Register Azure AD application (if not done)
- [ ] Add Graph API permissions:
  - [ ] Mail.ReadWrite (delegated)
  - [ ] Contacts.ReadWrite (delegated)
  - [ ] Files.ReadWrite.All (delegated)
- [ ] Create client secret with appropriate expiration
- [ ] Note down:
  - [ ] Tenant ID
  - [ ] Client ID
  - [ ] Client Secret
  - [ ] Shared mailbox email

### WordPress Configuration
- [ ] Update WordPress options with Azure credentials:
  ```php
  wp option update lgp_shared_mailbox "support@..."
  wp option update lgp_azure_tenant_id "..."
  wp option update lgp_azure_client_id "..."
  wp option update lgp_azure_client_secret "..."
  ```
- [ ] Or configure via WordPress admin settings page

### Database Setup
- [ ] Ensure custom post types exist:
  - [ ] `ticket` post type
  - [ ] `contact` post type
- [ ] Create database indexes:
  ```sql
  ALTER TABLE wp_postmeta ADD INDEX idx_email_message_id ...
  ALTER TABLE wp_postmeta ADD INDEX idx_conversation_id ...
  ALTER TABLE wp_commentmeta ADD INDEX idx_comment_email_message_id ...
  ```

## Phase 2: Code Integration

### File Structure
- [ ] Create/verify class files:
  - [ ] `/includes/class-lgp-graph-client.php` ✓
  - [ ] `/includes/class-lgp-email-ingest.php` ✓
  - [ ] `/includes/class-lgp-email-reply.php` ✓
  - [ ] `/includes/email-integration.php` ✓
  - [ ] `/includes/class-lgp-logger.php` (verify exists)

### Main Plugin File Integration
- [ ] Add to `wp-poolsafe-portal.php`:
  ```php
  require_once PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
  require_once PLUGIN_DIR . 'includes/class-lgp-email-ingest.php';
  require_once PLUGIN_DIR . 'includes/class-lgp-email-reply.php';
  require_once PLUGIN_DIR . 'includes/email-integration.php';
  ```
- [ ] Verify plugin loads without errors: `wp plugin list`
- [ ] Check debug log for errors: `/wp-content/debug.log`

### Scheduled Events
- [ ] Verify WordPress cron hooks:
  - [ ] `lgp_sync_emails` registered (5-minute interval)
  - [ ] `lgp_detect_outlook_replies` registered (10-minute interval)
- [ ] Test cron execution: `wp eval 'do_action("lgp_sync_emails");'`

### REST API Endpoints
- [ ] Verify endpoints are registered:
  - [ ] `POST /wp-json/lgp/v1/email/sync`
  - [ ] `POST /wp-json/lgp/v1/email/send-reply`
  - [ ] `GET /wp-json/lgp/v1/email/ticket-status/{id}`
- [ ] Test endpoint: `curl http://localhost/wp-json/lgp/v1/email/send-reply -X POST`

## Phase 3: Testing

### Unit Tests
- [ ] Test Graph Client connection
- [ ] Test email sync creation
- [ ] Test idempotency
- [ ] Test attachment processing
- [ ] Test reply sending
- [ ] Test Outlook reply detection

### Integration Tests
- [ ] End-to-end: email → ticket → reply → Outlook detection
- [ ] Multiple conversations
- [ ] Attachment handling
- [ ] Contact creation

### Manual Testing
- [ ] Send test email to shared mailbox
- [ ] Verify ticket created with metadata:
  - [ ] `_email_source` = 1
  - [ ] `_sender_email` = sender address
  - [ ] `_email_message_id` = Graph message ID
  - [ ] `_email_conversation_id` = Conversation ID
- [ ] Add reply via portal
- [ ] Verify email sent to original sender
- [ ] Verify comment has `_email_sent` = 1
- [ ] Send reply from Outlook
- [ ] Verify reply detected and recorded

### Edge Cases
- [ ] Email with no subject (defaults to "(No Subject)")
- [ ] Email with special characters in subject
- [ ] Email with HTML content
- [ ] Email with plain text
- [ ] Email with large attachments
- [ ] Email with multiple attachments
- [ ] Email with no attachments
- [ ] Empty email body
- [ ] Very long email body

## Phase 4: Logging and Monitoring

### Logging Setup
- [ ] Create log directory: `/wp-content/logs/`
- [ ] Set permissions: `chmod 755 /wp-content/logs/`
- [ ] Verify logs created:
  - [ ] `/wp-content/logs/email-ingest.log`
  - [ ] `/wp-content/logs/email-reply.log`

### Log Verification
- [ ] Run sync: `wp eval 'do_action("lgp_sync_emails");'`
- [ ] Check logs for entries
- [ ] Verify error messages are clear
- [ ] Test with invalid credentials to see error handling

### Monitoring
- [ ] Set up log monitoring (optional):
  - [ ] CloudWatch for AWS
  - [ ] Application Insights for Azure
  - [ ] Third-party logging service
- [ ] Create admin alerts for errors
- [ ] Monitor sync success rate

## Phase 5: Security Hardening

### Credentials Management
- [ ] Move credentials from database to environment:
  - [ ] `$_ENV['LGP_AZURE_TENANT_ID']`
  - [ ] `$_ENV['LGP_AZURE_CLIENT_ID']`
  - [ ] `$_ENV['LGP_AZURE_CLIENT_SECRET']`
- [ ] Update code to read from environment
- [ ] Remove from wp-options (or use fallback)
- [ ] Document in deployment guide

### Permissions Audit
- [ ] Verify least privilege:
  - [ ] Azure app has only needed permissions
  - [ ] Shared mailbox access limited to service account
  - [ ] WordPress users can only reply to assigned tickets
- [ ] Review Azure app permissions
- [ ] Review WordPress user roles

### Attachment Security
- [ ] Verify file validation:
  - [ ] File type checking
  - [ ] Size limits enforced
  - [ ] Path traversal prevention
- [ ] Store attachments outside web root
- [ ] Implement virus scanning (optional):
  - [ ] ClamAV
  - [ ] VirusTotal API

### API Security
- [ ] Verify nonce validation on POST endpoints
- [ ] Check permission checks on all endpoints
- [ ] Implement rate limiting (optional)
- [ ] Add request signing/validation

## Phase 6: Performance Optimization

### Query Optimization
- [ ] Verify database indexes created
- [ ] Test queries with EXPLAIN:
  ```sql
  EXPLAIN SELECT ... FROM wp_postmeta WHERE meta_key = '_email_message_id'
  ```
- [ ] Optimize post meta queries if needed

### Caching
- [ ] Implement token caching:
  - [ ] `lgp_graph_access_token` transient (1 hour)
  - [ ] `lgp_email_delta_token` transient (24 hours)
- [ ] Cache Graph API responses where appropriate
- [ ] Test cache invalidation

### Batch Processing
- [ ] Verify email sync handles bulk:
  - [ ] Test with 100+ emails
  - [ ] Verify memory stays reasonable
  - [ ] Check execution time (target: < 30 seconds)
- [ ] Implement queue system if needed (optional)

## Phase 7: Documentation

### Documentation Files
- [ ] Review all docs:
  - [ ] `SHARED_MAILBOX_SETUP.md` ✓
  - [ ] `EMAIL_SCHEMA.md` ✓
  - [ ] `EMAIL_API_REFERENCE.md` ✓
  - [ ] `EMAIL_TEST_CASES.md` ✓
- [ ] Update main README.md with email feature
- [ ] Create quick-start guide

### Code Documentation
- [ ] Verify all classes have docblocks
- [ ] Verify all methods have documentation
- [ ] Verify examples are accurate

### User Documentation
- [ ] Create user guide for replying via portal
- [ ] Document email threading behavior
- [ ] Document attachment limits
- [ ] Create troubleshooting guide

## Phase 8: Deployment

### Pre-Production
- [ ] Test in staging environment
- [ ] Run full test suite
- [ ] Performance test with production-like data
- [ ] Security audit
- [ ] Load test (optional)

### Production Deployment
- [ ] Backup database
- [ ] Create database indexes
- [ ] Deploy code to production
- [ ] Update WordPress options with production credentials
- [ ] Verify plugin loads: `wp plugin list`
- [ ] Test cron: `wp eval 'do_action("lgp_sync_emails");'`
- [ ] Check logs for errors
- [ ] Send test email to shared mailbox
- [ ] Verify ticket creation
- [ ] Test reply sending

### Post-Deployment
- [ ] Monitor logs for errors
- [ ] Check email sync runs automatically
- [ ] Verify reply detection works
- [ ] Get user feedback
- [ ] Monitor performance

## Phase 9: Maintenance

### Regular Tasks
- [ ] Weekly: Review error logs
- [ ] Weekly: Test manual sync
- [ ] Monthly: Verify cron jobs running
- [ ] Monthly: Check Azure app permissions
- [ ] Quarterly: Review access logs
- [ ] Quarterly: Update dependencies

### Updates and Patching
- [ ] Monitor Microsoft Graph API changes
- [ ] Update code if API changes
- [ ] Test updates in staging
- [ ] Deploy updates to production

### Archival
- [ ] Archive old tickets (older than 1 year):
  ```php
  wp eval 'do_action("lgp_archive_old_tickets");'
  ```
- [ ] Cleanup old logs
- [ ] Backup archived data

## Phase 10: Advanced Features (Optional)

### Future Enhancements
- [ ] Auto-assign based on email domain
- [ ] Auto-categorize by subject keywords
- [ ] Priority detection (URGENT, etc)
- [ ] Auto-response templates
- [ ] CRM integration (HubSpot, Salesforce)
- [ ] Calendar integration
- [ ] Shared calendar invites
- [ ] Email signature standardization
- [ ] Spam filtering
- [ ] Message templates

### Feature Implementation Pattern
When adding features:
1. Add new method to appropriate class
2. Add tests for new feature
3. Add documentation
4. Add admin settings if user-configurable
5. Test integration with existing features
6. Deploy and monitor

## Rollback Plan

If issues occur in production:

### Immediate (< 1 hour)
- [ ] Disable email syncing: `wp option update lgp_email_enabled 0`
- [ ] Disable reply sending: `wp option update lgp_reply_enabled 0`
- [ ] Check error logs for root cause
- [ ] Notify users of temporary limitation

### Short-term (1-24 hours)
- [ ] Fix issue in development
- [ ] Test in staging
- [ ] Deploy fix to production
- [ ] Resume email processing
- [ ] Verify queue/catch-up

### Long-term
- [ ] Full root cause analysis
- [ ] Implement additional tests
- [ ] Update monitoring/alerts
- [ ] Document issue and resolution

## Success Criteria

The implementation is complete when:

- [ ] All emails to shared mailbox create tickets
- [ ] All ticket replies are sent via email
- [ ] All Outlook replies are detected and recorded
- [ ] Attachments are downloaded and stored
- [ ] Contacts are created from email senders
- [ ] Conversation threading is maintained
- [ ] All errors are logged and handled gracefully
- [ ] Performance is acceptable (< 30 second sync)
- [ ] Security is hardened (no credential leaks)
- [ ] Documentation is complete and accurate
- [ ] Users can easily reply to tickets via portal
- [ ] Users receive email replies properly threaded
- [ ] No data loss or corruption occurs
- [ ] Monitoring/alerts are in place
- [ ] All tests pass

## Sign-Off

- [ ] Development Lead: _________________ Date: _______
- [ ] QA Lead: _________________ Date: _______
- [ ] Security Review: _________________ Date: _______
- [ ] DevOps/Deployment: _________________ Date: _______
- [ ] Project Manager: _________________ Date: _______

---

## Notes

Use this section for implementation notes, blockers, and progress:

```
[Space for notes]
```

---

**Last Updated**: 2024-01-15
**Status**: Ready for implementation
**Version**: 1.0
