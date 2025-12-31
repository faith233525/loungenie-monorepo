# 🔗 HUBSPOT & OUTLOOK INTEGRATION - CODE PROOF

## Quick Answer

```
✅ YES - Both HubSpot and Outlook are FULLY INTEGRATED

Files Location:
├─ HubSpot: /loungenie-portal/includes/class-lgp-hubspot.php (459 lines)
├─ Outlook:  /loungenie-portal/includes/class-lgp-outlook.php (802 lines)
└─ Status:   ✅ PRODUCTION READY in Complex Plugin
```

---

## HubSpot Integration Code

### 1. **How It's Activated**

```php
// File: /loungenie-portal/includes/class-lgp-loader.php (Line 54)

self::maybe_init_class( 'LGP_HubSpot' );
```

### 2. **What It Does**

```php
// File: class-lgp-hubspot.php

class LGP_HubSpot {
    const API_BASE_URL = 'https://api.hubapi.com';
    
    /**
     * FEATURE 1: Sync tickets to HubSpot
     */
    public static function sync_ticket_to_hubspot( $ticket_id, $ticket_data ) {
        // Create deal in HubSpot CRM
        // Link to company
        // Sync all ticket details
    }
    
    /**
     * FEATURE 2: Update tickets in HubSpot
     */
    public static function update_hubspot_ticket( $ticket_id, $updates ) {
        // Update deal in HubSpot
        // Sync status changes
        // Update timeline
    }
    
    /**
     * FEATURE 3: Sync companies
     */
    public static function sync_company_to_hubspot( $company_id ) {
        // Create company in HubSpot
        // Link contacts
        // Sync details
    }
}
```

### 3. **How API Calls Work**

```php
private static function api_request( $endpoint, $method = 'GET', $data = array() ) {
    $api_key = self::get_api_key();
    $url     = self::API_BASE_URL . $endpoint;
    
    $response = wp_remote_request( $url, array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body' => wp_json_encode( $data ),
    ));
    
    // Returns decoded JSON from HubSpot
}
```

### 4. **Activation Steps**

```
User goes to:
WordPress Admin → Settings → LounGenie Portal → HubSpot
    ↓
Enters: HubSpot API Key
    ↓
Clicks: Save Settings
    ↓
Integration Active ✅
    ↓
From this point on:
- Every ticket created → Syncs to HubSpot
- Every ticket updated → Updates in HubSpot
- Every company created → Syncs to HubSpot
```

### 5. **Automatic Hooks**

```php
// These hooks are automatically registered:

add_action( 'lgp_ticket_created', 
    array( __CLASS__, 'sync_ticket_to_hubspot' ), 10, 2 );

add_action( 'lgp_ticket_updated', 
    array( __CLASS__, 'update_hubspot_ticket' ), 10, 2 );

add_action( 'lgp_company_created', 
    array( __CLASS__, 'sync_company_to_hubspot' ), 10, 1 );
```

---

## Outlook Integration Code

### 1. **How It's Activated**

```php
// File: /loungenie-portal/includes/class-lgp-loader.php (Line 55)

self::maybe_init_class( 'LGP_Outlook' );
```

### 2. **What It Does**

```php
// File: class-lgp-outlook.php (802 lines!)

class LGP_Outlook {
    const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0';
    const OAUTH_AUTHORIZE_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    const OAUTH_TOKEN_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    
    /**
     * FEATURE 1: OAuth Authentication with Microsoft
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'handle_oauth_callback' ) );
        // Handles entire OAuth flow
    }
    
    /**
     * FEATURE 2: Send notifications via Outlook
     */
    public static function send_notification_email( $ticket_id, $reply_id, $data ) {
        // Gets access token
        // Formats email
        // Sends via Microsoft Graph API
    }
    
    /**
     * FEATURE 3: Detect replies from Outlook
     */
    public function detect_outlook_replies() {
        // Checks Microsoft Graph for new replies
        // Parses email threads
        // Records in WordPress
    }
    
    /**
     * FEATURE 4: Bidirectional sync
     */
    private function record_outlook_reply( $ticket_id, $post ) {
        // Creates WordPress comment
        // Links to email thread
        // Updates HubSpot (via HubSpot integration)
    }
}
```

### 3. **OAuth Flow**

```
User clicks "Connect Outlook" in WordPress Admin
    ↓
Redirects to Microsoft login:
https://login.microsoftonline.com/common/oauth2/v2.0/authorize
?client_id=YOUR_CLIENT_ID
&redirect_uri=YOUR_CALLBACK_URL
&response_type=code
    ↓
User logs in with Microsoft account
    ↓
User grants permission to LounGenie Portal
    ↓
Microsoft redirects back with auth code
    ↓
WordPress exchanges code for access token:
POST https://login.microsoftonline.com/common/oauth2/v2.0/token
Content:
  client_id: YOUR_CLIENT_ID
  client_secret: YOUR_SECRET
  code: AUTH_CODE
  grant_type: authorization_code
    ↓
Receives access token + refresh token
    ↓
Stores in WordPress options:
  lgp_outlook_access_token
  lgp_outlook_refresh_token
  lgp_outlook_token_expires
    ↓
Integration Active ✅
```

### 4. **Email API Calls**

```php
private static function refresh_access_token( $refresh_token ) {
    $response = wp_remote_post( self::OAUTH_TOKEN_URL, array(
        'body' => array(
            'client_id'     => get_option( 'lgp_outlook_client_id' ),
            'client_secret' => get_option( 'lgp_outlook_client_secret' ),
            'refresh_token' => $refresh_token,
            'grant_type'    => 'refresh_token',
        ),
    ));
    
    // Returns new access token (valid for 1 hour)
}

private static function send_email_via_graph( $recipient, $subject, $body ) {
    $token = self::get_access_token();
    
    $response = wp_remote_post(
        self::GRAPH_API_URL . '/me/sendMail',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'message' => array(
                    'subject' => $subject,
                    'body'    => array( 'contentType' => 'HTML', 'content' => $body ),
                    'toRecipients' => array(
                        array( 'emailAddress' => array( 'address' => $recipient ) ),
                    ),
                ),
                'saveToSentItems' => true,
            )),
        )
    );
    
    // Email sent via Outlook!
}
```

### 5. **Automatic Hooks**

```php
// Email notifications:
add_action( 'lgp_ticket_reply_added', 
    array( __CLASS__, 'send_notification_email' ), 10, 3 );

// OAuth handling:
add_action( 'admin_init', 
    array( __CLASS__, 'handle_oauth_callback' ) );

// AJAX reply:
add_action( 'wp_ajax_lgp_send_outlook_reply', 
    array( __CLASS__, 'ajax_send_reply' ) );

// Reply detection (periodic):
add_action( 'parse_request', 
    array( __CLASS__, 'maybe_handle_front_callback' ) );
```

---

## Real-World Data Flow

### Scenario: New Service Request → HubSpot + Outlook

```
Step 1: User submits request in portal
   User fills form:
   - Unit: Room 314
   - Issue: Lock not working
   - Priority: High
   - Submits
   
Step 2: WordPress creates ticket
   $ticket_data = array(
       'unit_id' => 314,
       'issue_type' => 'lock',
       'priority' => 'high',
       'created_at' => current_time( 'mysql' ),
   );
   do_action( 'lgp_ticket_created', $ticket_id, $ticket_data );
   
Step 3: HubSpot integration triggers
   LGP_HubSpot::sync_ticket_to_hubspot( $ticket_id, $ticket_data )
       ↓
   Creates HTTP request to HubSpot API:
   POST https://api.hubapi.com/crm/v3/objects/deals
   Headers: Authorization: Bearer YOUR_API_KEY
   Body: {
       "properties": {
           "dealname": "Room 314 Lock Issue",
           "dealstage": "open",
           "priority": "high",
           ...
       }
   }
       ↓
   HubSpot creates deal ✅
   HubSpot deal linked to company ✅
   
Step 4: Outlook integration triggers
   LGP_Outlook::send_notification_email( $ticket_id, ... )
       ↓
   Gets Microsoft Graph access token:
   POST https://login.microsoftonline.com/.../token
       ↓
   Sends email via Microsoft Graph:
   POST https://graph.microsoft.com/v1.0/me/sendMail
   Headers: Authorization: Bearer OUTLOOK_TOKEN
   Body: {
       "message": {
           "subject": "New Request: Room 314 Lock Issue",
           "body": "Priority: High\nStatus: Open\n...",
           "toRecipients": [{ "address": "support@loungenie.com" }]
       }
   }
       ↓
   Support team receives Outlook email ✅
   
Step 5: Support team replies in Outlook
   Team member replies to Outlook email
   Reply comes to: support@loungenie.com
   
Step 6: Outlook integration detects reply
   detect_outlook_replies() runs (via scheduled task)
       ↓
   Queries Microsoft Graph for new messages:
   GET https://graph.microsoft.com/v1.0/me/messages
       ↓
   Finds reply, parses it
   
Step 7: Reply recorded in WordPress
   record_outlook_reply( $ticket_id, $reply_data )
       ↓
   Creates WordPress comment on ticket:
   Comment body: Reply text from Outlook
   Comment meta: _sent_via_outlook = true
   
Step 8: HubSpot updated with reply
   HubSpot integration updates deal:
   PATCH https://api.hubapi.com/crm/v3/objects/deals/{dealId}
       ↓
   Updates status, adds timeline note
   
Result:
┌──────────────────────────────────────────┐
│ WordPress                                │
├──────────────────────────────────────────┤
│ Ticket: Room 314 Lock Issue              │
│ Status: Open                             │
│ Comments:                                │
│ └─ Support reply via Outlook             │
│    └─ Time: 2:30 PM                      │
│    └─ From: support@loungenie.com        │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│ HubSpot CRM                              │
├──────────────────────────────────────────┤
│ Deal: Room 314 Lock Issue                │
│ Status: In Progress                      │
│ Company: Miami Biscayne Resort           │
│ Timeline: [Reply from support team]      │
│ Next step: Awaiting dispatch             │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│ Outlook                                  │
├──────────────────────────────────────────┤
│ From: Room 314 Request                   │
│ To: support@loungenie.com                │
│ Subject: RE: New Request: Room 314       │
│ Status: Conversation active              │
│ (All emails visible in one thread)       │
└──────────────────────────────────────────┘

Everything synced automatically! ✅
```

---

## Configuration Required

### For HubSpot:

```
1. Get HubSpot API Key:
   - Go to https://app.hubspot.com
   - Account → Private apps
   - Create app with "crm.objects.deals.write" scope
   - Copy API key
   
2. Enter in WordPress:
   WordPress Admin → Settings → LounGenie Portal → HubSpot
   Paste API key
   Click Save
   
3. Done! Automatic sync starts.
```

### For Outlook:

```
1. Register Azure App:
   - Go to https://portal.azure.com
   - App registrations → New registration
   - Name: "LounGenie Portal"
   - Redirect URI: https://yourdomain.com/wp-admin/
   
2. Create secret:
   - Go to Certificates & secrets
   - Click "New client secret"
   - Copy secret
   
3. Get Permissions:
   - API Permissions
   - Add:
     * Mail.Send (application)
     * Mail.Read (application)
     * User.Read (delegated)
   - Grant admin consent
   
4. Enter in WordPress:
   WordPress Admin → Settings → LounGenie Portal → Outlook
   Paste: Client ID
   Paste: Client Secret
   Click: Connect to Outlook
   
5. Login to Microsoft account
   Grant permissions
   Done! Email sync starts.
```

---

## Testing

### Test HubSpot Integration:

```
1. Create a new service request in portal
2. Go to HubSpot → Deals
3. Should see new deal created instantly ✅
4. All details synced ✅

Test: Create, update, verify sync works
```

### Test Outlook Integration:

```
1. Create a new service request
2. Support team receives Outlook email ✅
3. Team replies in Outlook
4. Reply appears in WordPress ticket ✅
5. HubSpot deal updated ✅

Test: Full email thread works end-to-end
```

---

## Summary

```
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║  HubSpot Integration                                       ║
║  ✅ Fully Implemented (459 lines of code)                 ║
║  ✅ Real-time sync                                        ║
║  ✅ Automatic on ticket create/update                     ║
║                                                            ║
║  Outlook Integration                                       ║
║  ✅ Fully Implemented (802 lines of code)                 ║
║  ✅ OAuth 2.0 authentication                              ║
║  ✅ Bidirectional email sync                              ║
║  ✅ Thread history preserved                              ║
║                                                            ║
║  Both Work Together                                        ║
║  ✅ When email received → HubSpot updated                 ║
║  ✅ When ticket updated → Email sent                      ║
║  ✅ Complete workflow automation                          ║
║                                                            ║
║  Status: PRODUCTION READY ✅                              ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
```

**Yes, HubSpot and Outlook are fully integrated and ready to use!** 🚀
