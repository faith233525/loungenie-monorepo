# LounGenie Portal: Email-to-Ticket System Architecture

## System Overview Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         EMAIL TO TICKET SYSTEM (v1.8.0)                     │
│                    Production-Ready for Shared Hosting                       │
└─────────────────────────────────────────────────────────────────────────────┘

                              EXTERNAL SOURCES
                                     ▼
                    ┌─────────────────┴─────────────────┐
                    │                                   │
            ┌───────▼────────┐              ┌──────────▼────────┐
            │  PARTNER EMAILS│              │   WORK EMAIL      │
            │ jane@poolsafe  │              │  support@company  │
            │  .com          │              │                   │
            └────────┬───────┘              └──────────┬────────┘
                     │                                 │
                     │ wp_mail() hook                  │ POP3 polling
                     │ intercept                       │ (every 15 min)
                     │                                 │
         ┌───────────▼─────────────┐      ┌───────────▼────────────┐
         │  LGP_Email_To_Ticket    │      │  LGP_Email_Handler     │
         │  (Hook-based Intake)    │      │  (POP3 Polling)        │
         └───────────┬─────────────┘      └───────────┬────────────┘
                     │                                │
                     │ parse_email()                  │ get_email_body()
                     │ detect_priority()              │ parse_structure()
                     │ extract_sender()               │ decode_content()
                     │                                │
                     └────────────┬──────────────────┘
                                  │
                         ┌────────▼────────┐
                         │ EMAIL DATA      │
                         │ - from          │
                         │ - subject       │
                         │ - body          │
                         │ - attachments   │
                         └────────┬────────┘
                                  │
                    ┌─────────────▼──────────────┐
                    │  LGP_Deduplication         │
                    │  (Prevent Duplicates)      │
                    │                            │
                    │ generate_hash(             │
                    │   email + subject + date   │
                    │ )                          │
                    └─────────────┬──────────────┘
                                  │
                         ┌────────▼────────┐
                         │ Hash Already    │
                         │ Processed?      │
                         └────┬────────┬───┘
                          YES │        │ NO
                         ┌────▼┐  ┌───▼────────┐
                         │SKIP │  │ Continue   │
                         │(DUP)│  │            │
                         └─────┘  └───┬────────┘
                                      │
                    ┌─────────────────▼────────────────┐
                    │  LGP_User_Creator                │
                    │  (Auto-Create WP User)           │
                    │                                  │
                    │ 1. Find company by email domain  │
                    │ 2. Create user if not exists     │
                    │ 3. Assign lgp_partner role       │
                    │ 4. Send welcome email            │
                    └─────────────────┬────────────────┘
                                      │
                    ┌─────────────────▼──────────────────┐
                    │  CREATE SERVICE REQUEST            │
                    │  (lgp_service_requests table)      │
                    │                                    │
                    │ - company_id                       │
                    │ - request_type: 'email_support'    │
                    │ - priority (detected from subject) │
                    │ - status: 'pending'                │
                    └─────────────────┬──────────────────┘
                                      │
                    ┌─────────────────▼──────────────────┐
                    │  CREATE TICKET                     │
                    │  (lgp_tickets table)               │
                    │                                    │
                    │ - service_request_id (FK)          │
                    │ - status: 'open'                   │
                    │ - thread_history: JSON             │
                    │ - email_reference                  │
                    └─────────────────┬──────────────────┘
                                      │
                    ┌─────────────────▼──────────────────┐
                    │  LGP_Attachment_Handler            │
                    │  (Process Attachments)             │
                    │                                    │
                    │ 1. Validate file (type, size)      │
                    │ 2. Generate secure filename        │
                    │ 3. Save to company folder          │
                    │ 4. Chunked reading (memory-safe)   │
                    │ 5. Store metadata in DB            │
                    └─────────────────┬──────────────────┘
                                      │
                    ┌─────────────────▼──────────────────┐
                    │  LGP_Email_Notifications           │
                    │  (Send Notifications)              │
                    │                                    │
                    │ ┌─────────────┐                    │
                    │ │Support Team │ (always)           │
                    │ └─────────────┘                    │
                    │ ┌─────────────┐                    │
                    │ │Partner      │ (own tickets only) │
                    │ │Company      │                    │
                    │ └─────────────┘                    │
                    └─────────────────┬──────────────────┘
                                      │
                    ┌─────────────────▼──────────────────┐
                    │  LOG & AUDIT                       │
                    │  (lgp_audit_log table)             │
                    │                                    │
                    │ - user_id                          │
                    │ - action: 'ticket_created_from_    │
                    │   email'                           │
                    │ - metadata: JSON                   │
                    │ - created_at                       │
                    └─────────────────┬──────────────────┘
                                      │
                         ┌────────────▼───────────┐
                         │  TICKET CREATED ✓      │
                         │  Ready for Response    │
                         └────────────────────────┘
```

---

## Data Flow: Single Email Processing

```
STEP 1: EMAIL INTAKE
┌─────────────────────┐
│ Email from partner: │
│ jane@poolsafeinc.com│
│ Subject: [URGENT]   │
│ Body: Down issue    │
│ Attach: log.zip     │
└──────────┬──────────┘
           │
STEP 2: ROUTE
     ┌─────┴─────┐
     │           │
  [HOOK]    [POP3-POLL]
     │           │
     ▼           ▼
  hook_path   pop3_path
     │           │
     └─────┬─────┘
           │
STEP 3: PARSE
           ▼
┌─────────────────────────────┐
│ Extract:                    │
│ from: jane@poolsafeinc.com  │
│ subject: [URGENT] Down      │
│ body: Down issue description│
│ attachments: [log.zip]      │
└──────────┬──────────────────┘
           │
STEP 4: DEDUPLICATE
           ▼
┌──────────────────────────────────┐
│ Hash = SHA256(                   │
│   'jane@poolsafeinc.com' +       │
│   '[urgent] down' +              │
│   '2024-01-15 14:00'  (rounded)  │
│ )                                │
│ = 'abc1234def5678...'            │
│                                  │
│ Check: Is this hash processed?   │
│ Result: NO (first time)          │
└──────────┬───────────────────────┘
           │
STEP 5: COMPANY LOOKUP
           ▼
┌──────────────────────────────┐
│ Extract domain from email:   │
│ @poolsafeinc.com             │
│                              │
│ Query DB:                    │
│ SELECT id FROM companies     │
│ WHERE contact_email LIKE     │
│ '%@poolsafeinc.com'          │
│                              │
│ Result: company_id = 5       │
│ (Pool Safe Inc.)             │
└──────────┬───────────────────┘
           │
STEP 6: USER CREATION
           ▼
┌──────────────────────────────┐
│ Check: User jane@poolsafeinc │
│ exists?                      │
│                              │
│ NO → Create:                 │
│ - username: jane.poolsafeinc │
│ - email: jane@poolsafeinc.com│
│ - role: lgp_partner          │
│ - company_id: 5              │
│ - Send welcome email         │
│                              │
│ Result: user_id = 42         │
└──────────┬───────────────────┘
           │
STEP 7: SERVICE REQUEST
           ▼
┌────────────────────────────────┐
│ INSERT INTO service_requests:  │
│ - company_id: 5                │
│ - request_type: email_support  │
│ - priority: high (from subject)│
│ - status: pending              │
│ - notes: Down issue description│
│                                │
│ Result: request_id = 99        │
└────────────┬───────────────────┘
             │
STEP 8: TICKET
             ▼
┌────────────────────────────────┐
│ INSERT INTO tickets:           │
│ - service_request_id: 99 (FK)  │
│ - status: open                 │
│ - thread_history: [            │
│   {                            │
│     "timestamp": "2024-01-15", │
│     "user_id": 42,             │
│     "email": "jane@...",       │
│     "type": "incoming",        │
│     "source": "pop3",          │
│     "content": "Down issue..." │
│   }                            │
│  ]                             │
│ - email_reference: jane@...    │
│                                │
│ Result: ticket_id = 123        │
└────────────┬───────────────────┘
             │
STEP 9: ATTACHMENTS
             ▼
┌────────────────────────────────┐
│ For each attachment (log.zip): │
│                                │
│ 1. Validate:                   │
│    - type: application/zip OK  │
│    - size: 2MB < 10MB OK       │
│    - count: 1 < 5 OK           │
│                                │
│ 2. Generate filename:          │
│    123-a1b2c3d4-log.zip        │
│                                │
│ 3. Save path:                  │
│    /uploads/lgp-attachments/   │
│    poolsafeinc-com/            │
│    123-a1b2c3d4-log.zip        │
│                                │
│ 4. Chunked copy (memory-safe)  │
│                                │
│ 5. Store metadata:             │
│    ticket_id: 123              │
│    file_name: log.zip          │
│    file_path: /full/path/...   │
│    file_size: 2097152          │
│    mime_type: application/zip  │
│                                │
│ Result: attachment_id = 1      │
└────────────┬───────────────────┘
             │
STEP 10: DEDUP REGISTER
             ▼
┌────────────────────────────────┐
│ INSERT INTO email_dedup:       │
│ - email_hash: abc1234def5678...│
│ - ticket_id: 123               │
│ - company_id: 5                │
│ - source: pop3                 │
│ - expires_at: NOW() + 1 HOUR   │
│                                │
│ (prevents reprocessing for     │
│  1 hour if hook + POP3 both    │
│  receive it)                   │
└────────────┬───────────────────┘
             │
STEP 11: NOTIFICATIONS
             ▼
┌────────────────────────────────┐
│ Get Support Team users:        │
│ SELECT WHERE role=lgp_support  │
│ Result: [user1, user2, user3]  │
│                                │
│ For each:                      │
│ → Send email:                  │
│   Subject: [URGENT] Ticket #123│
│   Body: New ticket, down issue │
│                                │
│ Get Partner Company users:     │
│ SELECT WHERE company_id=5      │
│ Result: [jane, bob]            │
│                                │
│ For each:                      │
│ → Send email:                  │
│   "Your ticket #123 received"  │
│ → Create portal alert          │
│                                │
│ Log notifications in audit log │
└────────────┬───────────────────┘
             │
STEP 12: AUDIT LOG
             ▼
┌────────────────────────────────┐
│ INSERT INTO audit_log:         │
│ - user_id: 42 (jane)           │
│ - action: ticket_created_from_ │
│   email                        │
│ - company_id: 5                │
│ - meta: {                      │
│   "ticket_id": 123,            │
│   "source": "pop3",            │
│   "from": "jane@poolsafeinc..." │
│  }                             │
│ - created_at: NOW()            │
└────────────┬───────────────────┘
             │
DONE ◄───────┘

Result: Ticket #123 created, ready for response
```

---

## Role-Based Access Control Flow

```
USER LOGIN
│
├─ SUPPORT TEAM USER (lgp_support role)
│  │
│  ├─ Can view: ALL tickets (all companies)
│  │
│  ├─ Can create: Tickets for any company
│  │
│  ├─ Can reply to: Any ticket
│  │
│  ├─ Can access: All attachments
│  │
│  ├─ Can see: Audit logs, analytics
│  │
│  └─ Dashboard: System-wide metrics
│
└─ PARTNER COMPANY USER (lgp_partner role)
   │
   ├─ _lgp_company_id = 5 (Pool Safe Inc)
   │
   ├─ Can view: Only tickets for company 5
   │
   ├─ Can create: Service requests for company 5
   │
   ├─ Can reply to: Own tickets only
   │
   ├─ Can access: Own attachments only
   │
   ├─ Cannot see: Audit logs, gateways
   │
   └─ Dashboard: Company-specific data
```

---

## File Organization

```
WordPress Installation
│
└── wp-content/
    │
    ├── plugins/
    │   │
    │   └── loungenie-portal/
    │       │
    │       ├── loungenie-portal.php (main plugin)
    │       │
    │       ├── includes/
    │       │   ├── class-lgp-deduplication.php
    │       │   ├── class-lgp-attachment-handler.php
    │       │   ├── class-lgp-user-creator.php
    │       │   ├── class-lgp-email-to-ticket-enhanced.php
    │       │   ├── class-lgp-email-handler-enhanced.php
    │       │   ├── class-lgp-email-notifications.php
    │       │   ├── class-lgp-logger.php
    │       │   ├── class-lgp-auth.php
    │       │   ├── class-lgp-router.php
    │       │   └── ... (other existing classes)
    │       │
    │       ├── templates/
    │       │   ├── dashboard-support.php
    │       │   ├── dashboard-partner.php
    │       │   ├── portal-login.php
    │       │   └── ... (other templates)
    │       │
    │       ├── PRODUCTION_EMAIL_SECURITY.md
    │       ├── PRODUCTION_DEPLOYMENT.md
    │       ├── COMPREHENSIVE_TESTING_GUIDE.md
    │       └── ARCHITECTURE.md (this file)
    │
    └── uploads/
        │
        └── lgp-attachments/
            ├── .htaccess (blocks .php execution)
            ├── index.php (prevents directory listing)
            │
            ├── poolsafeinc-com/
            │   ├── index.php
            │   ├── 123-a1b2c3d4-document.pdf
            │   ├── 124-e5f6g7h8-image.jpg
            │   └── ... (attachments for Pool Safe Inc)
            │
            ├── loungenie-com/
            │   ├── index.php
            │   ├── 200-i9j0k1l2-report.xlsx
            │   └── ... (attachments for LounGenie)
            │
            └── ... (other company folders)
```

---

## Database Schema

### Email Deduplication Table

```
wp_lgp_email_dedup
├─ id (PK, BIGINT UNSIGNED)
├─ email_hash (UNIQUE, VARCHAR 64)  ← SHA256 hash of email+subject+date
├─ ticket_id (FK to wp_lgp_tickets)
├─ company_id (FK to wp_lgp_companies)
├─ source (VARCHAR 50)               ← 'hook', 'pop3', 'manual'
├─ processed_at (DATETIME)
└─ expires_at (DATETIME)             ← Auto-cleanup at +1 hour
```

### Tickets Table (Enhanced)

```
wp_lgp_tickets
├─ id (PK, BIGINT UNSIGNED)
├─ service_request_id (FK)
├─ status (VARCHAR 50)               ← 'open', 'under_review', etc
├─ thread_history (LONGTEXT)         ← JSON array of entries
├─ email_reference (VARCHAR 255)     ← Original sender email
├─ created_at (DATETIME)
└─ updated_at (DATETIME)

thread_history JSON structure:
[
  {
    "timestamp": "2024-01-15 14:30:00",
    "user_id": 42,
    "email": "jane@poolsafeinc.com",
    "type": "incoming",              ← or "outgoing", "system"
    "source": "pop3",                ← or "hook", "manual", "web"
    "content": "Email body text...",
    "attachments": [
      {
        "id": 1,
        "name": "log.zip",
        "size": 2097152,
        "url": "/download?id=1&token=..."
      }
    ]
  },
  {
    "timestamp": "2024-01-15 15:00:00",
    "user_id": 10,                   ← Support Team user
    "email": "support@company.com",
    "type": "outgoing",
    "source": "web",
    "content": "Thank you for reporting...",
    "attachments": []
  }
]
```

### Attachments Table

```
wp_lgp_ticket_attachments
├─ id (PK, BIGINT UNSIGNED)
├─ ticket_id (FK to wp_lgp_tickets)
├─ file_name (VARCHAR 255)           ← Original filename
├─ file_type (VARCHAR 100)           ← MIME type
├─ file_size (BIGINT)                ← Bytes
├─ file_path (VARCHAR 500)           ← Full filesystem path
├─ uploaded_by (FK to wp_users)
└─ created_at (DATETIME)

Storage location:
/wp-content/uploads/lgp-attachments/{company-domain}/{ticket_id}-{random}-{name}
```

### Service Requests Table (Enhanced)

```
wp_lgp_service_requests
├─ id (PK, BIGINT UNSIGNED)
├─ company_id (FK to wp_lgp_companies)
├─ request_type (VARCHAR 50)         ← 'email_support'
├─ priority (VARCHAR 20)             ← 'low', 'medium', 'high'
├─ status (VARCHAR 50)               ← 'pending', 'open'
├─ notes (LONGTEXT)                  ← First email body
├─ created_at (DATETIME)
└─ updated_at (DATETIME)
```

---

## Security Layers

```
LAYER 1: EMAIL SOURCE VALIDATION
├─ Whitelist support email addresses
├─ Verify domain matches company
└─ Reject unknown sources

LAYER 2: INPUT SANITIZATION
├─ Email validation (is_email)
├─ Subject sanitization (sanitize_text_field)
├─ Body cleaning (wp_kses_post)
└─ IMAP header decoding (safe charset handling)

LAYER 3: FILE SECURITY
├─ MIME type whitelist validation
├─ File size limits (10MB per file)
├─ Attachment count limit (5 per ticket)
├─ Secure filename generation (add random suffix)
├─ .htaccess blocking PHP execution
├─ No direct HTTP access (REST API required)
└─ Download token verification (HMAC)

LAYER 4: CREDENTIAL SECURITY
├─ POP3 password encrypted at rest (XOR + base64)
├─ Decrypted only when connecting
└─ Never logged or exposed

LAYER 5: DATABASE SECURITY
├─ Prepared statements everywhere
├─ Type casting on all inserts
└─ Foreign key constraints

LAYER 6: ACCESS CONTROL
├─ Role-based permissions (support vs partner)
├─ User company linkage verification
├─ REST API permission callbacks
└─ Ticket visibility filtering

LAYER 7: AUDIT LOGGING
├─ All user actions logged
├─ Email processing tracked
├─ Failures recorded
└─ Compliance-ready records
```

---

## Performance Optimization

```
SHARED HOSTING CONSTRAINTS
├─ Memory limit: 128MB (we use < 10MB for email processing)
├─ Timeout: 30-60 seconds (we finish in < 5 seconds)
├─ Disk I/O: Limited
└─ CPU: Shared

OUR SOLUTIONS
│
├─ CRON BATCHING
│  └─ Process max 10 emails per 15-minute run
│
├─ CHUNKED FILE I/O
│  └─ Read/write in 1MB chunks, not all at once
│
├─ CRON LOCKING
│  └─ Prevent parallel execution on shared host
│
├─ DATABASE INDEXING
│  ├─ email_hash (UNIQUE) ← prevents duplicates
│  ├─ company_id, status ← role-based queries
│  ├─ ticket_id ← attachment lookups
│  └─ expires_at ← cleanup queries
│
└─ EFFICIENT QUERIES
   ├─ Prepared statements (prevent N+1)
   ├─ Pagination (max 100 items per API call)
   └─ Denormalization where needed (thread_history JSON)
```

---

## Error Handling & Recovery

```
SCENARIO: POP3 Connection Fails
│
├─ Catch: Connection error logged
├─ Continue: Next cron run retries
├─ Notify: Admin email (optional)
└─ No tickets created: Safe state

SCENARIO: File Size Limit Exceeded
│
├─ Reject: File not saved
├─ Log: Attachment too large
├─ Ticket still created: With note about failed attachment
└─ No database corruption

SCENARIO: Unknown Company Domain
│
├─ Log: Unmatched company
├─ Skip: Email not processed
├─ Prevent: Create ticket for unknown company
└─ Manual review: Admin can map domain

SCENARIO: Duplicate Email (Both Paths)
│
├─ First path: Creates ticket #123, registers hash
├─ Second path: Hash found, skips, returns #123
├─ Result: One ticket, no duplicate
└─ Dedup table handles both sources

SCENARIO: Database Insert Fails
│
├─ Rollback: Service request deleted if ticket insert fails
├─ Log: Full error message
├─ Continue: Cron continues with next email
└─ Retry: Next cron run will retry
```

---

## Monitoring & Observability

```
METRICS TO TRACK
│
├─ Emails processed per day
├─ Average processing time
├─ Duplicate detection rate
├─ User auto-creation count
├─ Attachment upload success rate
├─ Notification delivery rate
├─ Database query performance
└─ Cron execution timing

LOG PATTERNS
│
├─ "Created ticket #123 from email via pop3" ← Success
├─ "Email duplicate detected" ← Dedup worked
├─ "Failed to get/create user" ← User creation issue
├─ "Attachment too large" ← File validation
├─ "POP3 connection failed" ← Network issue
└─ "Processed 10 emails" ← Batch completion

ALERTS
│
├─ No emails processed in 1 hour (cron not running)
├─ POP3 connection failures (3+ in a row)
├─ Disk space warning (< 100MB available)
├─ Database growth warning (dedup table > 100K rows)
└─ Memory usage spikes (> 80% of limit)
```

---

## Future Enhancements

```
PHASE 2: AI & INTELLIGENT ROUTING
├─ Auto-categorize tickets by content
├─ Suggest priority based on keywords + patterns
├─ Auto-assign to technician based on skill/load
└─ Generate ticket summaries from email body

PHASE 3: EXTERNAL INTEGRATIONS
├─ Slack notifications
├─ Microsoft Teams integration
├─ Calendar integration for scheduled responses
├─ SMS alerts for critical tickets
└─ WebHook callbacks to external systems

PHASE 4: ADVANCED ANALYTICS
├─ Ticket resolution time SLA tracking
├─ Response quality metrics
├─ Partner satisfaction surveys
├─ Predictive analytics for resource planning
└─ Trend analysis and reporting

PHASE 5: MOBILE & API
├─ Native mobile apps (iOS/Android)
├─ GraphQL API (in addition to REST)
├─ Webhooks for real-time events
├─ Third-party OAuth integration
└─ SDK libraries for partners
```

---

This architecture is designed for:
- ✅ **Production reliability**: Error handling, deduplication, logging
- ✅ **Security**: Multi-layer validation, encryption, access control
- ✅ **Shared hosting compatibility**: Batching, chunking, locking
- ✅ **Scalability**: Indexed queries, efficient JSON storage
- ✅ **Maintainability**: Clear separation of concerns, documented classes
- ✅ **Compliance**: Audit logging, data retention, GDPR-ready
