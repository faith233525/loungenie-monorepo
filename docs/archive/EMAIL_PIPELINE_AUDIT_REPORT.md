# Email Pipeline Security & Architecture Audit Report

**Date**: December 19, 2025  
**Auditor**: GitHub Copilot (Claude Sonnet 4.5)  
**Scope**: LounGenie Portal email pipeline (Graph-based, shared mailbox)

---

## Executive Summary

### Overall Status: ⚠️ **MODERATE RISK** - Requires immediate attention

**Critical Issues Found**: 4  
**High Priority Issues**: 8  
**Medium Priority Issues**: 6  
**Low Priority Issues**: 3

### Key Findings

1. ✅ **Feature flag isolation works** - legacy pipeline correctly prevented when new pipeline active
2. ❌ **CRITICAL: Secrets stored in database** - Azure credentials saved via `update_option()`
3. ❌ **CRITICAL: No permission checks on logger** - `LGP_Logger::log_error()` method doesn't exist
4. ⚠️ **Multiple duplicate email classes** - `LGP_Email_Handler` vs `LGP_Shared_Mailbox` vs `LGP_Email_Ingest`
5. ⚠️ **Race conditions possible** - lock mechanism exists but has gaps
6. ⚠️ **Idempotency incomplete** - mixed strategies across files

---

## 1. Email Pipeline Architecture

### Feature Flag Logic: ✅ **CORRECT**

**Location**: [class-lgp-loader.php](loungenie-portal/includes/class-lgp-loader.php#L135-L155)

```php
// Priority: Constant > Env var > Option
if ( defined( 'LGP_EMAIL_PIPELINE' ) ) {
    return 'new' === LGP_EMAIL_PIPELINE || true === LGP_EMAIL_PIPELINE || 1 === LGP_EMAIL_PIPELINE;
}
$env = getenv( 'LGP_EMAIL_PIPELINE' );
if ( $env ) {
    $env = strtolower( trim( $env ) );
    return in_array( $env, array( 'new', 'true', '1', 'on' ), true );
}
return (bool) get_option( 'lgp_use_new_email_pipeline', false );
```

**✅ Strengths**:
- Correct precedence order (constant > env > option)
- Prevents legacy handler from initializing when new pipeline active
- Consistent between loader and main plugin file

**❌ Issues**:
- No admin UI to set `lgp_use_new_email_pipeline` option
- No migration guide for switching pipelines
- **CRITICAL**: Main plugin file duplicates this logic instead of calling loader method

**Recommendation**:
```php
// In loungenie-portal.php, replace lines 189-195 with:
if ( LGP_Loader::use_new_email_pipeline() ) {
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
    // ...
}
```

---

## 2. Ingest Pipeline

### Classes Involved

| Class | Purpose | Status |
|-------|---------|--------|
| `LGP_Email_Handler` | Legacy POP3/Graph hybrid | ⚠️ Active (if flag disabled) |
| `LGP_Shared_Mailbox` | New Graph-only handler | ⚠️ Never initialized in loader |
| `LGP_Email_Ingest` | New pipeline ingest logic | ⚠️ Never initialized in loader |
| `LGP_Email_Reply` | Reply handler | ⚠️ Never initialized in loader |
| `LGP_Email_To_Ticket` | Converter (used by both) | ✅ Always loaded |

### ❌ **CRITICAL ISSUE #1: New Pipeline Classes Not Initialized**

**Problem**: [class-lgp-loader.php](loungenie-portal/includes/class-lgp-loader.php#L44-L54) only prevents legacy handler but doesn't initialize new pipeline:

```php
// Phase 4: Features (optional, independently initialized)
if ( ! self::use_new_email_pipeline() ) {
    // Legacy POP3/Graph hybrid handler
    LGP_Email_Handler::init();
}
// ❌ Missing: LGP_Shared_Mailbox::init() or LGP_Email_Ingest initialization
```

**Impact**: New pipeline is non-functional unless manually triggered elsewhere.

**Location**: [loungenie-portal.php](loungenie-portal/loungenie-portal.php#L189-L204) conditionally loads files but never calls `::init()`:

```php
if ( $use_new_email ) {
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-ingest.php';
    require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-reply.php';
    require_once LGP_PLUGIN_DIR . 'includes/email-integration.php'; // ✅ This sets up hooks
}
```

**Actual Initialization**: Happens in [email-integration.php](loungenie-portal/includes/email-integration.php#L28-L43):
```php
add_action( 'wp_loaded', function() {
    if ( ! wp_next_scheduled( 'lgp_sync_emails' ) ) {
        wp_schedule_event( time(), '5-minute', 'lgp_sync_emails' );
    }
    // ...
    wp_clear_scheduled_hook( 'lgp_process_emails' ); // ✅ GOOD: Deconflicts legacy
    wp_clear_scheduled_hook( 'lgp_sync_shared_mailbox' );
} );
```

**Recommendation**:
1. Move new pipeline initialization to `LGP_Loader::init()`:
   ```php
   if ( self::use_new_email_pipeline() ) {
       LGP_Shared_Mailbox::init(); // or create unified entry point
   } else {
       LGP_Email_Handler::init();
   }
   ```
2. Document which class is the canonical entry point for new pipeline

---

## 3. Graph API Integration

### Delta Token Handling: ⚠️ **NEEDS IMPROVEMENT**

**Locations**:
1. [class-lgp-email-handler.php](loungenie-portal/includes/class-lgp-email-handler.php#L119-L141)
2. [class-lgp-email-ingest.php](loungenie-portal/includes/class-lgp-email-ingest.php#L61-L99)
3. [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L63-L84)

**✅ Correct Pattern**:
```php
$staged_delta = $response['delta_token'] ?? null;
// ... process messages ...
if ( ! empty( $staged_delta ) ) {
    $settings['delta_token'] = $staged_delta;
    update_option( self::$graph_option_key, $settings ); // ✅ Only commit after success
}
```

**⚠️ Issues**:
1. **Multiple delta storage keys**:
   - `lgp_graph_settings` (in `LGP_Email_Handler`)
   - `lgp_email_delta_token` (transient in `LGP_Email_Ingest`)
   - `lgp_graph_delta_token` (option in `LGP_Shared_Mailbox`)
   
   **Impact**: Concurrent runs could stomp each other's delta tokens.

2. **Delta token URL handling**: [class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php#L141-L148)
   ```php
   if ( $delta_token ) {
       // delta token is a full URL; call directly
       $body = $this->request( 'GET', str_replace( $this->base_url, '', $delta_token ) );
   }
   ```
   ✅ **Correct**: Delta URLs from Graph API are full URLs, not paths.

**Recommendation**:
- Standardize on single delta key: `lgp_graph_delta_token_{mailbox_hash}`
- Store in transients (24hr expiry) instead of permanent options

---

## 4. Idempotency & Deduplication

### Strategy: ⚠️ **MIXED APPROACHES**

#### Approach 1: Database Check (email_reference column)
**Location**: [class-lgp-email-to-ticket.php](loungenie-portal/includes/class-lgp-email-to-ticket.php#L50-L62)

```php
$internet_id = $message['internetMessageId'] ?? '';
if ( ! empty( $internet_id ) ) {
    $existing_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}lgp_tickets WHERE email_reference = %s LIMIT 1",
            $internet_id
        )
    );
    if ( $existing_id ) {
        return (int) $existing_id; // ✅ GOOD: Early return
    }
}
```

✅ **Strengths**: Reliable, indexed column

#### Approach 2: Options Cache
**Location**: [class-lgp-email-to-ticket.php](loungenie-portal/includes/class-lgp-email-to-ticket.php#L65-L70)

```php
$cache = get_option( 'lgp_graph_processed_ids', array() );
if ( in_array( $internet_id, (array) $cache, true ) ) {
    return false;
}
// ... after success ...
$cache[] = $internet_id;
if ( count( $cache ) > 500 ) { $cache = array_slice( $cache, -500 ); }
update_option( 'lgp_graph_processed_ids', $cache, false );
```

⚠️ **Issues**:
- Array stored in single option (500 items = ~15KB serialized)
- Linear search (`in_array`) on every message
- Race condition: Two processes can read/write simultaneously
- Not indexed - slow on shared hosting

#### Approach 3: Dedicated Dedup Table
**Location**: [class-lgp-deduplication.php](loungenie-portal/includes/class-lgp-deduplication.php#L15-L56)

```php
const DEDUP_TABLE = 'lgp_email_dedup';
const HASH_WINDOW = 3600; // 1 hour

public static function generate_hash( $sender_email, $subject, $date ) {
    $timestamp = strtotime( $date );
    $normalized_time = ( $timestamp / 60 ) * 60; // Round to minute
    $data = $sender_email . '|' . $subject . '|' . $normalized_time;
    return hash( 'sha256', $data );
}
```

✅ **Strengths**:
- Proper table with UNIQUE index on `email_hash`
- Time-based expiration (`expires_at` column)
- Handles clock skew (rounds to nearest minute)

❌ **Issues**:
- **Never used** - class exists but no callers found
- Table created but not populated
- Orphaned code

**Recommendation**:
1. **Standardize on database column approach** (`email_reference` in `lgp_tickets`)
2. **Remove** `lgp_graph_processed_ids` option cache (race-prone)
3. **Keep** `LGP_Deduplication` class but integrate into `ingest_graph_message()`
4. Add composite index: `(email_reference, created_at)` for cleanup queries

---

## 5. Cron & Scheduling

### ✅ **GOOD: Deconfliction Logic Present**

**Location**: [email-integration.php](loungenie-portal/includes/email-integration.php#L28-L43)

```php
add_action( 'wp_loaded', function() {
    if ( ! wp_next_scheduled( 'lgp_sync_emails' ) ) {
        wp_schedule_event( time(), '5-minute', 'lgp_sync_emails' );
    }
    if ( ! wp_next_scheduled( 'lgp_detect_outlook_replies' ) ) {
        wp_schedule_event( time() + 120, '10-minute', 'lgp_detect_outlook_replies' );
    }

    // ✅ Deconflict: ensure legacy schedulers disabled
    if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
        wp_clear_scheduled_hook( 'lgp_process_emails' ); // legacy handler
        wp_clear_scheduled_hook( 'lgp_sync_shared_mailbox' ); // legacy shared mailbox
    }
} );
```

### Cron Jobs Registered

| Hook | Interval | Handler | Purpose |
|------|----------|---------|---------|
| `lgp_process_emails` | 5 min | `LGP_Email_Handler::process_emails()` | **Legacy** POP3/Graph |
| `lgp_sync_emails` | 5 min | `LGP_Email_Ingest::sync_messages()` | **New** Graph ingest |
| `lgp_detect_outlook_replies` | 10 min | `LGP_Email_Reply::detect_outlook_replies()` | Detect Outlook-sent replies |
| `lgp_sync_shared_mailbox` | ? | `LGP_Shared_Mailbox::sync_inbox()` | **Duplicate?** of new pipeline |

### ⚠️ **Race Condition Risks**

#### Issue 1: Lock Release in Exception Path
**Location**: [class-lgp-email-handler.php](loungenie-portal/includes/class-lgp-email-handler.php#L95-L157)

```php
$lock_key = 'lgp_graph_sync_lock';
if ( get_transient( $lock_key ) ) {
    return; // ❌ Silent return - no logging of skip reason in catch block
}
set_transient( $lock_key, 1, 5 * MINUTE_IN_SECONDS );

try {
    // ... sync logic ...
} catch ( Exception $e ) {
    // ✅ Logs error
} finally {
    delete_transient( $lock_key ); // ✅ GOOD: finally block ensures release
}
```

✅ **Correct**: Uses `finally` block.

**But**: No such lock in `LGP_Email_Ingest::sync_messages()` or `LGP_Shared_Mailbox::sync_inbox()`.

#### Issue 2: No Lock on Delta Update
Multiple processes can read stale delta, process same messages, then overwrite with different deltas.

**Recommendation**:
```php
// Atomic delta update with optimistic locking
$lock_acquired = wp_cache_add( 'lgp_delta_lock', 1, '', 300 );
if ( ! $lock_acquired ) {
    return; // Another sync in progress
}

try {
    $delta = get_option( 'lgp_graph_delta_token' );
    $response = $client->get_messages( $delta );
    // ... process ...
    update_option( 'lgp_graph_delta_token', $response['delta_token'] );
} finally {
    wp_cache_delete( 'lgp_delta_lock' );
}
```

---

## 6. Configuration & Secrets

### ❌ **CRITICAL ISSUE #2: Secrets in Database**

**Problem**: Azure client secrets stored in `wp_options` table.

**Locations**:
1. [class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php#L58-L61)
   ```php
   $client_secret = $client_secret ?: get_option( 'lgp_azure_client_secret' );
   ```

2. [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L715)
   ```php
   <input type="password" name="lgp_shared_mailbox_settings[client_secret]" 
          value="<?php echo esc_attr( $settings['client_secret'] ?? '' ); ?>">
   ```

3. [class-lgp-microsoft-sso.php](loungenie-portal/includes/class-lgp-microsoft-sso.php#L92)
   ```php
   $client_secret = get_option( self::OPTION_CLIENT_SECRET, '' );
   ```

**Impact**: 
- Secrets exposed in database backups
- Accessible via SQL injection if other plugin is vulnerable
- Visible in admin UI (type="password" doesn't prevent value export)

### ✅ **CORRECT: Env Var Priority**

[class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php#L51-L67) implements correct resolution order:

```php
// 1. Explicit settings
$client_secret = $settings['client_secret'] ?? null;

// 2. Env fallback (✅ GOOD)
$client_secret = $client_secret ?: getenv( 'LGP_AZURE_CLIENT_SECRET' );

// 3. Options fallback (❌ BAD: should error instead)
$client_secret = $client_secret ?: get_option( 'lgp_azure_client_secret' );
```

### Safe Failure Mode: ⚠️ **PARTIALLY CORRECT**

[class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L42-L45)
```php
if ( empty( $settings['tenant_id'] ) || empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) ) {
    throw new Exception( 'Shared mailbox Graph settings not configured' );
}
```

✅ Fails fast if misconfigured  
❌ Exception not caught - can crash admin UI  

**Recommendation**:
1. **Remove** database secret storage entirely
2. **Require** env vars for production: `LGP_AZURE_CLIENT_SECRET`
3. Update admin UI to show status instead of input:
   ```php
   $has_secret = ! empty( getenv( 'LGP_AZURE_CLIENT_SECRET' ) );
   echo $has_secret 
       ? '<span class="dashicons dashicons-yes-alt"></span> Configured via environment'
       : '<span class="dashicons dashicons-warning"></span> Not configured';
   ```

---

## 7. Security & Permissions

### ❌ **CRITICAL ISSUE #3: Logger Method Doesn't Exist**

**Problem**: Code calls `LGP_Logger::log_error()` but method doesn't exist.

**Locations**:
- [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L96)
- [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L158)
- [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L402)
- [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L481)

**Actual Signature**: [class-lgp-logger.php](loungenie-portal/includes/class-lgp-logger.php#L95-L99)
```php
public static function log( $type, $action, $meta = array(), $user_id = null, $company_id = null ) {
    // 5 parameters required
}
```

**Impact**: All error logging silently fails in shared mailbox class.

**Recommendation**:
```php
// Add missing method to LGP_Logger:
public static function log_error( $message, $context = array() ) {
    return self::log( 'error', $message, $context, get_current_user_id(), null );
}
```

### REST API Endpoints: ⚠️ **NO EMAIL ENDPOINTS FOUND**

Searched for `register_rest_route.*email` - no results.

**Good**: Email operations not exposed via REST (attack surface minimized).

**Question**: How do users trigger manual sync? WP-Cron only?

### Admin UI Permission Checks: ⚠️ **INCONSISTENT**

| File | Check Present | Line |
|------|---------------|------|
| `class-lgp-microsoft-sso.php` | ✅ `manage_options` | [L87](loungenie-portal/includes/class-lgp-microsoft-sso.php#L87) |
| `class-lgp-shared-mailbox.php` | ❌ **Missing** | Settings page has no cap check |
| `class-lgp-outlook.php` | ✅ `manage_options` | [L577](loungenie-portal/includes/class-lgp-outlook.php#L577) |

**Recommendation**: Add to `LGP_Shared_Mailbox::add_settings_page()`:
```php
public static function add_settings_page() {
    add_options_page(
        'Shared Mailbox Settings',
        'Shared Mailbox',
        'manage_options', // ✅ Add this
        'lgp-shared-mailbox',
        array( __CLASS__, 'render_settings_page' )
    );
}
```

### Email Spoofing Risk: ✅ **MITIGATED**

[class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php#L388-L399)
```php
// Optional: Add "on behalf of" header if support user has mailbox
if ( ! empty( $settings['allow_send_on_behalf'] ) && $user ) {
    $message_data['message']['from'] = array(
        'emailAddress' => array(
            'address' => $settings['mailbox'] ?? '', // ✅ Always shared mailbox
            'name'    => $user_display,
        ),
    );
}
```

✅ **Correct**: From address is always shared mailbox, not user email.

**But**: `allow_send_on_behalf` setting has no validation - could be enabled by non-admins if settings page lacks permission check.

---

## 8. Threading & Outlook Detection

### Threading Metadata: ✅ **CORRECT**

[class-lgp-email-ingest.php](loungenie-portal/includes/class-lgp-email-ingest.php#L142-L149)
```php
update_post_meta( $ticket_id, '_email_message_id', $message['id'] );
update_post_meta( $ticket_id, '_email_conversation_id', $message['conversationId'] ?? '' );
update_post_meta( $ticket_id, '_email_internet_message_id', $message['internetMessageId'] ?? '' );
```

✅ Stores all necessary Graph IDs for reply threading.

### Outlook Reply Detection: ⚠️ **CLASS NOT FOUND**

[email-integration.php](loungenie-portal/includes/email-integration.php#L70-L79) registers cron:
```php
add_action( 'lgp_detect_outlook_replies', function() {
    try {
        $reply_handler = new LGP_Email_Reply(); // ❌ Never initialized anywhere
        $count = $reply_handler->detect_outlook_replies();
    } catch ( Exception $e ) {
        error_log( 'Outlook reply detection failed: ' . $e->getMessage() );
    }
} );
```

**Problem**: `LGP_Email_Reply::detect_outlook_replies()` method not defined in [class-lgp-email-reply.php](loungenie-portal/includes/class-lgp-email-reply.php).

**Recommendation**: Either implement method or remove cron job.

---

## 9. Migrations & Database

### Email Reference Column: ✅ **EXISTS**

[class-lgp-database.php](loungenie-portal/includes/class-lgp-database.php#L146)
```sql
CREATE TABLE wp_lgp_tickets (
    ...
    email_reference varchar(255),
    ...
)
```

✅ Column exists  
❌ No index on `email_reference` (used in WHERE clause for idempotency)

**Recommendation**:
```sql
ALTER TABLE wp_lgp_tickets ADD INDEX idx_email_reference (email_reference);
```

### Dedup Table: ⚠️ **CREATED BUT UNUSED**

[class-lgp-deduplication.php](loungenie-portal/includes/class-lgp-deduplication.php#L33-L56) creates table but:
- Never called in any email handler
- No data inserted
- Orphaned code

**Recommendation**: Either integrate or remove.

### Migration Safety: ⚠️ **NO EMAIL-SPECIFIC MIGRATIONS**

No version checks or schema updates for:
- Adding `email_reference` index
- Migrating delta tokens between option keys
- Cleaning up legacy `lgp_graph_processed_ids` cache

**Recommendation**: Create migration in `class-lgp-migrations.php`:
```php
public static function migrate_email_schema_v2() {
    global $wpdb;
    
    // Add index
    $wpdb->query( "ALTER TABLE {$wpdb->prefix}lgp_tickets 
                   ADD INDEX IF NOT EXISTS idx_email_reference (email_reference)" );
    
    // Migrate delta tokens
    $old_delta = get_option( 'lgp_email_delta_token' );
    if ( $old_delta ) {
        update_option( 'lgp_graph_delta_token', $old_delta );
        delete_option( 'lgp_email_delta_token' );
    }
    
    // Clean up cache
    delete_option( 'lgp_graph_processed_ids' );
}
```

---

## 10. Dead / Duplicate Code

### Orphaned Classes

| Class | Status | Reason |
|-------|--------|--------|
| `LGP_Deduplication` | 🗑️ **Remove** | Created but never used |
| `LGP_Email_Notifications` | ⚠️ **Audit** | Auto-initialized but unclear if active |

### Duplicate Functionality

| Feature | Class 1 | Class 2 | Class 3 |
|---------|---------|---------|---------|
| Graph message fetching | `LGP_Email_Handler` | `LGP_Email_Ingest` | `LGP_Shared_Mailbox` |
| Ticket creation | `LGP_Email_To_Ticket` | `LGP_Shared_Mailbox` | - |
| Delta token storage | `lgp_graph_settings` | `lgp_email_delta_token` | `lgp_graph_delta_token` |

**Recommendation**: Consolidate into single entry point:
```php
class LGP_Email_Pipeline {
    private $client;  // LGP_Graph_Client
    private $ingest;  // LGP_Email_To_Ticket
    
    public static function init() {
        if ( ! self::is_configured() ) return;
        
        self::schedule_cron();
        add_action( 'lgp_sync_emails', [ __CLASS__, 'sync' ] );
    }
    
    public static function sync() {
        $lock = new LGP_Lock( 'email_sync', 300 );
        if ( ! $lock->acquire() ) return;
        
        try {
            $client = new LGP_Graph_Client();
            $delta = self::get_delta_token();
            $response = $client->get_messages( $delta );
            
            foreach ( $response['messages'] as $msg ) {
                LGP_Email_To_Ticket::ingest_graph_message( $msg, $attachments );
            }
            
            self::save_delta_token( $response['delta_token'] );
        } finally {
            $lock->release();
        }
    }
}
```

### Unused Methods

**Location**: [class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php#L243-L282)

```php
public function mark_as_read( $message_id ) { ... }  // ❌ No callers
public function get_folders() { ... }               // ❌ No callers
public function get_message( $message_id ) { ... }  // ❌ No callers (uses get_messages instead)
```

**Recommendation**: Keep for future use but add `@internal` doc tag.

### Hooks That Never Fire

[email-integration.php](loungenie-portal/includes/email-integration.php#L84-L112) registers `comment_post` hook to send email on portal reply:

```php
add_action( 'comment_post', function( $comment_id, $comment ) {
    if ( 'ticket_reply' !== $comment->comment_type ) return;
    // ...
    $reply_handler = new LGP_Email_Reply();
    $reply_handler->send_reply( $ticket_id, $reply_content, $author_id );
}, 10, 2 );
```

**Question**: Does portal UI actually use WordPress comments for ticket replies? Or custom table?

---

## 11. Performance Risks

### Large Dataset Scenarios

#### Risk 1: Unbounded Options Array
[class-lgp-email-to-ticket.php](loungenie-portal/includes/class-lgp-email-to-ticket.php#L137-L141)
```php
$cache = get_option( 'lgp_graph_processed_ids', array() );
$cache[] = $internet_id;
if ( count( $cache ) > 500 ) { $cache = array_slice( $cache, -500 ); }
update_option( 'lgp_graph_processed_ids', $cache, false );
```

**Impact**: 500 items × 40 bytes = ~20KB autoloaded on every request.

**Recommendation**: Use transients or dedicated table.

#### Risk 2: No Pagination on Initial Sync
[class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php#L141)
```php
$path = '/users/' . rawurlencode( $this->mailbox ) . '/mailFolders/Inbox/messages?$top=25&...';
```

✅ Limits to 25 messages per request  
❌ No handling of `@odata.nextLink` for pagination

**Scenario**: Mailbox with 1000+ unread emails on first sync will only process 25.

**Recommendation**:
```php
do {
    $response = $this->request( 'GET', $next_link ?? $initial_path );
    foreach ( $response['messages'] as $msg ) { /* process */ }
    $next_link = $response['@odata.nextLink'] ?? null;
} while ( $next_link && $processed < 100 ); // Limit per cron run
```

#### Risk 3: No Cleanup of Old Tickets
`email_reference` column grows unbounded. No TTL or archival strategy.

**Recommendation**: Add cron to delete old processed IDs:
```php
DELETE FROM wp_lgp_tickets WHERE email_reference IS NOT NULL AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 12. Summary of Critical Actions

### Immediate (Before Production)

1. ✅ **Fix loader initialization** - Add `LGP_Shared_Mailbox::init()` or document new pipeline entry point
2. ❌ **Add `LGP_Logger::log_error()` method** - 4 calls will fail otherwise
3. ❌ **Remove database secret storage** - Require env vars, update admin UI
4. ❌ **Add index**: `ALTER TABLE wp_lgp_tickets ADD INDEX idx_email_reference (email_reference);`
5. ⚠️ **Add permission check** to `LGP_Shared_Mailbox::add_settings_page()` - `manage_options` capability

### High Priority (Within 2 Weeks)

6. ⚠️ **Standardize delta token storage** - Single key, transient-based
7. ⚠️ **Remove `lgp_graph_processed_ids` option cache** - Use database column only
8. ⚠️ **Implement proper locking** for delta updates (atomic read-modify-write)
9. ⚠️ **Add pagination support** to Graph client for large mailboxes
10. ⚠️ **Consolidate duplicate classes** - Decide on canonical email pipeline entry point

### Medium Priority (Within 1 Month)

11. ⚠️ **Integrate or remove** `LGP_Deduplication` class
12. ⚠️ **Implement `detect_outlook_replies()` method** or remove cron job
13. ⚠️ **Document pipeline architecture** - Which class handles what?
14. ⚠️ **Add email schema migration** - Index, delta cleanup, etc.

### Low Priority (Nice to Have)

15. 📝 Add admin UI for `lgp_use_new_email_pipeline` option
16. 📝 Add manual sync button (with nonce + capability check)
17. 📝 Implement email archival/cleanup strategy
18. 📝 Add monitoring dashboard for sync status

---

## 13. Architectural Recommendations

### Proposed Unified Structure

```
includes/
├── email/
│   ├── class-lgp-email-pipeline.php      ← Single entry point
│   ├── class-lgp-graph-client.php        ← Low-level API (keep)
│   ├── class-lgp-email-to-ticket.php     ← Converter (keep)
│   └── class-lgp-email-lock.php          ← Dedicated locking class
├── class-lgp-loader.php                   ← Calls LGP_Email_Pipeline::init()
```

### Deprecated Files (Mark for Removal)

```
includes/
├── class-lgp-email-handler.php            ← Legacy POP3/hybrid (deprecate)
├── class-lgp-email-ingest.php             ← Redundant with shared-mailbox
├── class-lgp-shared-mailbox.php           ← Merge into email-pipeline
├── class-lgp-email-reply.php              ← Incomplete implementation
├── class-lgp-deduplication.php            ← Unused
└── email-integration.php                  ← Move hooks to email-pipeline
```

---

## Appendix A: File Inventory

| File | Lines | Purpose | Status |
|------|-------|---------|--------|
| [class-lgp-email-handler.php](loungenie-portal/includes/class-lgp-email-handler.php) | 645 | Legacy POP3/Graph hybrid | ⚠️ Conditionally loaded |
| [class-lgp-graph-client.php](loungenie-portal/includes/class-lgp-graph-client.php) | 282 | Graph API wrapper | ✅ Active |
| [class-lgp-email-to-ticket.php](loungenie-portal/includes/class-lgp-email-to-ticket.php) | 548 | Email → ticket converter | ✅ Active (both pipelines) |
| [class-lgp-email-ingest.php](loungenie-portal/includes/class-lgp-email-ingest.php) | 580 | New pipeline ingest | ⚠️ Loaded but not initialized |
| [class-lgp-shared-mailbox.php](loungenie-portal/includes/class-lgp-shared-mailbox.php) | 743 | Shared mailbox handler | ⚠️ Loaded but not initialized |
| [class-lgp-email-reply.php](loungenie-portal/includes/class-lgp-email-reply.php) | 447 | Reply handler | ⚠️ Incomplete |
| [class-lgp-deduplication.php](loungenie-portal/includes/class-lgp-deduplication.php) | 197 | Dedup logic | 🗑️ Unused |
| [email-integration.php](loungenie-portal/includes/email-integration.php) | 305 | Hook registration | ✅ Active (new pipeline) |
| [class-lgp-email-notifications.php](loungenie-portal/includes/class-lgp-email-notifications.php) | ? | Notification emails | ⚠️ Audit needed |

**Total Lines of Email Code**: ~3,747

---

## Appendix B: Configuration Matrix

| Setting | Env Var | WP Option | Constant | Default |
|---------|---------|-----------|----------|---------|
| Pipeline mode | `LGP_EMAIL_PIPELINE` | `lgp_use_new_email_pipeline` | `LGP_EMAIL_PIPELINE` | `false` (legacy) |
| Tenant ID | `LGP_AZURE_TENANT_ID` | `lgp_azure_tenant_id` | - | - |
| Client ID | `LGP_AZURE_CLIENT_ID` | `lgp_azure_client_id` | - | - |
| Client Secret | `LGP_AZURE_CLIENT_SECRET` | ❌ `lgp_azure_client_secret` | - | - |
| Mailbox | `LGP_SHARED_MAILBOX` | `lgp_shared_mailbox` | - | - |
| Delta Token | - | `lgp_graph_delta_token` | - | - |

**Priority**: Constant > Env Var > WP Option

---

## Appendix C: Cron Schedule

| Hook | Interval | Legacy/New | Handler |
|------|----------|------------|---------|
| `lgp_process_emails` | 5 min | **Legacy** | `LGP_Email_Handler::process_emails()` |
| `lgp_sync_emails` | 5 min | **New** | Anonymous closure → `LGP_Email_Ingest::sync_messages()` |
| `lgp_detect_outlook_replies` | 10 min | **New** | Anonymous closure → `LGP_Email_Reply::detect_outlook_replies()` ❌ Missing |
| `lgp_sync_shared_mailbox` | ? | **Legacy** | `LGP_Shared_Mailbox::sync_inbox()` (cleared by new pipeline) |

---

## Sign-Off

**Audit Completed**: December 19, 2025  
**Next Review**: After critical issues resolved

**Risk Level**: ⚠️ **MODERATE** - System is functional but has security gaps and architectural debt.

**Cleared for Production**: ❌ **NOT RECOMMENDED** until:
1. Secrets moved to env vars
2. Logger method added
3. Database index created
4. Permission checks added to admin pages
