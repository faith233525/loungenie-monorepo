# Shared Mailbox Integration Setup Guide

## Overview

This guide provides step-by-step instructions for setting up the shared mailbox email integration for Pool Safe Portal.

## Prerequisites

- Microsoft 365 tenant with a shared mailbox
- Azure AD application registration with Graph API permissions
- WordPress admin access

## Step 1: Create/Configure Shared Mailbox in Microsoft 365

### Create Shared Mailbox

1. Go to **Microsoft 365 Admin Center** → **Resources** → **Shared mailboxes**
2. Click **+ Add a mailbox**
3. Configure:
   - **Mailbox name**: e.g., "support@poolsafe.example.com"
   - **Email address**: Choose your domain
4. Click **Create mailbox**
5. Add users who should have access (optional)

### Note the Mailbox Email Address

Save the shared mailbox email address (e.g., `support@poolsafe.example.com`). You'll need this.

## Step 2: Configure Azure AD Application

Your Azure AD app needs these **Graph API permissions** (delegated):

- `Mail.ReadWrite` - Read and write email
- `Calendars.ReadWrite` - (Optional) For calendar integration
- `Contacts.ReadWrite` - (Optional) For contact sync
- `Files.ReadWrite.All` - For attachment handling

### Add Permissions

1. Go to **Azure Portal** → **App registrations** → Your application
2. Click **API permissions**
3. Click **+ Add a permission**
4. Select **Microsoft Graph**
5. Choose **Delegated permissions**
6. Search for and add:
   - `Mail.ReadWrite`
   - `Contacts.ReadWrite` (optional)
7. Click **Grant admin consent**

### Create Client Secret (if not already done)

1. Go to **Certificates & secrets**
2. Click **+ New client secret**
3. Set expiration (e.g., 24 months)
4. Copy the secret value (you'll need this)

## Step 3: Configure Portal Settings

### Add Configuration Options

In WordPress admin, add these options (via Settings or manually):

```php
// Via wp-cli
wp option update lgp_shared_mailbox "support@poolsafe.example.com"
wp option update lgp_azure_client_id "YOUR_CLIENT_ID"
wp option update lgp_azure_client_secret "YOUR_CLIENT_SECRET"
wp option update lgp_azure_tenant_id "YOUR_TENANT_ID"
```

Or add to your settings page (admin):

1. Go to **Pool Safe Portal** → **Settings**
2. Under **Email Integration**:
   - **Shared Mailbox Email**: `support@poolsafe.example.com`
   - **Azure Tenant ID**: From Azure portal
   - **Azure Client ID**: From Azure app registration
   - **Azure Client Secret**: From Azure app (store securely!)

### Settings Page Code

Add this to your plugin settings (wp-admin/settings.php or similar):

```php
// Register settings
register_setting( 'lgp_email_settings', 'lgp_shared_mailbox' );
register_setting( 'lgp_email_settings', 'lgp_azure_client_id' );
register_setting( 'lgp_email_settings', 'lgp_azure_client_secret' );
register_setting( 'lgp_email_settings', 'lgp_azure_tenant_id' );

// Add settings section
add_settings_section(
    'lgp_email_settings',
    'Email Integration Settings',
    function() {
        echo 'Configure shared mailbox integration';
    },
    'lgp_email_settings'
);

// Shared mailbox field
add_settings_field(
    'lgp_shared_mailbox',
    'Shared Mailbox Email',
    function() {
        $value = get_option( 'lgp_shared_mailbox' );
        echo '<input type="email" name="lgp_shared_mailbox" value="' . esc_attr( $value ) . '" />';
    },
    'lgp_email_settings',
    'lgp_email_settings'
);

// Azure Tenant ID field
add_settings_field(
    'lgp_azure_tenant_id',
    'Azure Tenant ID',
    function() {
        $value = get_option( 'lgp_azure_tenant_id' );
        echo '<input type="text" name="lgp_azure_tenant_id" value="' . esc_attr( $value ) . '" />';
    },
    'lgp_email_settings',
    'lgp_email_settings'
);

// Client ID field
add_settings_field(
    'lgp_azure_client_id',
    'Azure Client ID',
    function() {
        $value = get_option( 'lgp_azure_client_id' );
        echo '<input type="text" name="lgp_azure_client_id" value="' . esc_attr( $value ) . '" />';
    },
    'lgp_email_settings',
    'lgp_email_settings'
);

// Client Secret field (with warning)
add_settings_field(
    'lgp_azure_client_secret',
    'Azure Client Secret',
    function() {
        $value = get_option( 'lgp_azure_client_secret' );
        echo '<input type="password" name="lgp_azure_client_secret" value="' . esc_attr( $value ) . '" />';
        echo '<p class="description">Stored securely. Use environment variables in production.</p>';
    },
    'lgp_email_settings',
    'lgp_email_settings'
);
```

## Step 4: Enable Required Features

### Activate Classes

Make sure these classes are loaded in your main plugin file (wp-poolsafe-portal.php):

```php
require_once PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
require_once PLUGIN_DIR . 'includes/class-lgp-email-ingest.php';
require_once PLUGIN_DIR . 'includes/class-lgp-email-reply.php';
require_once PLUGIN_DIR . 'includes/email-integration.php';
```

### Create Custom Post Type for Tickets (if not exists)

```php
add_action( 'init', function() {
    register_post_type( 'ticket', array(
        'label'              => 'Tickets',
        'public'             => true,
        'show_in_rest'       => true,
        'supports'           => array( 'title', 'editor', 'comments' ),
        'has_archive'        => true,
        'hierarchical'       => false,
    ) );
} );
```

### Create Custom Post Type for Contacts (if not exists)

```php
add_action( 'init', function() {
    register_post_type( 'contact', array(
        'label'              => 'Contacts',
        'public'             => false,
        'show_in_rest'       => true,
        'supports'           => array( 'title' ),
    ) );
} );
```

## Step 5: Test the Integration

### Manual Sync Test

1. Go to **WordPress REST API endpoint**:
   ```
   POST /wp-json/lgp/v1/email/sync
   ```

2. Or via wp-cli:
   ```bash
   wp eval 'do_action( "lgp_sync_emails" );'
   ```

3. Check logs in `/wp-content/logs/email-ingest.log`

### Expected Results

- New emails should create tickets
- Emails should have metadata attached:
  - `_email_source`: true
  - `_sender_email`: Original sender
  - `_email_message_id`: Graph Message ID
  - `_email_conversation_id`: For threading

### View Created Tickets

1. Go to **WordPress Admin** → **Posts** → **Tickets**
2. Look for tickets with titles matching email subjects

## Step 6: Configure Scheduled Syncing

The plugin automatically schedules:
- `lgp_sync_emails` - Every 5 minutes
- `lgp_detect_outlook_replies` - Every 10 minutes

### Enable WordPress Cron

Ensure WordPress cron is enabled:

```bash
# Check if wp-cron is disabled
wp config get DISABLE_WP_CRON

# If true, you need to set up real cron jobs
```

### Alternative: Use System Cron

If `DISABLE_WP_CRON` is true, set up real cron jobs:

```bash
# Sync emails every 5 minutes
*/5 * * * * curl -X POST http://yoursite.com/wp-json/lgp/v1/email/sync

# Detect Outlook replies every 10 minutes
*/10 * * * * wp eval 'do_action( "lgp_detect_outlook_replies" );'
```

## Step 7: Test Sending Replies

### Send Reply from Portal

1. Open a ticket created from email
2. Add a reply/comment
3. Reply should automatically:
   - Be sent to original sender via shared mailbox
   - Be recorded as comment on ticket
   - Include metadata about being sent via portal

### Test Manual Reply

```bash
curl -X POST http://yoursite.com/wp-json/lgp/v1/email/send-reply \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_id": 123,
    "content": "Thank you for your message!"
  }'
```

## Troubleshooting

### No Emails Imported

1. Check logger output:
   ```bash
   tail -f /wp-content/logs/email-ingest.log
   ```

2. Verify Graph API token:
   ```php
   $graph = new LGP_Graph_Client();
   $graph->test_connection(); // Should work
   ```

3. Verify shared mailbox access:
   - Check Azure app has `Mail.ReadWrite` permission
   - Check shared mailbox email is correct

### Replies Not Sending

1. Check permission:
   - App needs `Mail.Send` scope (add if missing)

2. Check error logs:
   ```bash
   tail -f /wp-content/logs/email-reply.log
   ```

3. Verify shared mailbox email is set correctly

### Outlook Replies Not Detected

1. Check conversation ID metadata:
   ```bash
   wp post meta get <ticket_id> _email_conversation_id
   ```

2. Verify `Conversations.Read` permission is granted

3. Check logs for sync errors

## Security Considerations

### Credential Storage

**Never** store credentials in code. Use:

1. **Environment Variables**:
   ```bash
   export LGP_AZURE_CLIENT_SECRET="your-secret"
   ```

2. **WordPress Secrets** (if using WordPress Secrets feature):
   ```php
   define( 'LGPMAIL_CLIENT_SECRET', getenv( 'LGP_AZURE_CLIENT_SECRET' ) );
   ```

3. **Secure Options** (encrypted):
   ```php
   // Use a secure option storage plugin
   update_option( 'lgp_azure_client_secret', $secret, '', 'no' );
   ```

### Permission Best Practices

- Use **delegate permissions**, not application permissions
- Consider using Graph API delegated auth with user consent
- Regularly audit Azure app permissions
- Implement activity logging for email operations

### Attachment Security

- Store attachments outside web root
- Validate file types before processing
- Implement virus scanning for attachments
- Set appropriate file permissions

## Next Steps

1. **Customize Email Templates**: Modify email formatting in `build_reply_message()`
2. **Add Attachment Upload**: Implement file upload to ticket creation
3. **Integrate with CRM**: Sync contacts with external CRM
4. **Add Notification Rules**: Notify specific users on new tickets
5. **Implement Queue System**: Use WP-Queue for processing large email volumes

## Support

For issues or questions:
1. Check the logs in `/wp-content/logs/`
2. Review error messages in WordPress admin
3. Test Graph API directly using Microsoft Graph Explorer
4. Check Azure app permissions and configuration

## API Reference

### LGP_Email_Ingest

```php
$ingest = new LGP_Email_Ingest();

// Sync messages from shared mailbox
$stats = $ingest->sync_messages();
// Returns: ['total' => 5, 'created' => 3, 'updated' => 2, 'skipped' => 0, 'errors' => 0]
```

### LGP_Email_Reply

```php
$reply = new LGP_Email_Reply();

// Send portal reply via email
$comment_id = $reply->send_reply(
    $ticket_id,
    $html_content,
    $author_id,
    $attachments
);

// Detect Outlook replies
$count = $reply->detect_outlook_replies();
```

### REST API Endpoints

- `POST /wp-json/lgp/v1/email/sync` - Manual sync
- `POST /wp-json/lgp/v1/email/send-reply` - Send reply
- `GET /wp-json/lgp/v1/email/ticket-status/{id}` - Get email status
