# 🎨 Page Update Workflow Architecture

## System Overview Diagram

```
┌───────────────────────────────────────────────────────────────────────┐
│                         GitHub Repository                              │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │                     .github/workflows/                           │  │
│  │                  update-pages-manual.yml                         │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                   ↓                                    │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │                      update-pages/                               │  │
│  │  ├── content/              (Page definitions)                    │  │
│  │  ├── media_lookup.json     (Image mappings)                      │  │
│  │  └── scripts/              (Update scripts)                      │  │
│  └─────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────────┘
                                   ↓
                       ┌───────────┴───────────┐
                       │  GitHub Actions Run    │
                       └───────────┬───────────┘
                                   ↓
        ┌──────────────────────────┼──────────────────────────┐
        ↓                          ↓                          ↓
┌───────────────┐        ┌───────────────┐        ┌──────────────────┐
│   Validate    │        │  Update Pages │        │ Verify Results   │
│ Prerequisites │   →    │   via REST    │   →    │ & Generate       │
│               │        │      API      │        │   Summary        │
└───────────────┘        └───────────────┘        └──────────────────┘
        ↓                          ↓                          ↓
   Check secrets            Process content              Check pages
   Verify creds            Create backups              Run verification
                           Apply updates
                           Upload artifacts
                                   ↓
                       ┌───────────┴───────────┐
                       │                       │
                       ↓                       ↓
              ┌────────────────┐      ┌────────────────┐
              │   WordPress    │      │   Artifacts    │
              │     Site       │      │   (Backups)    │
              │   (Updated)    │      │  (30 days)     │
              └────────────────┘      └────────────────┘
```

## Workflow Execution Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. User Triggers Workflow                                       │
│    GitHub UI or CLI → Run workflow → Configure options          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. Job: validate-prerequisites                                  │
│    ✓ Check WP_USERNAME exists                                   │
│    ✓ Check WP_APP_PASSWORD exists                               │
│    ✓ Check WP_SITE_URL exists                                   │
│    ✓ Output: secrets_valid = true/false                         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Job: update-pages (if prerequisites pass)                    │
│    Step 1: Checkout repository                                  │
│    Step 2: Set up Python 3.11                                   │
│    Step 3: Install dependencies (requests, etc.)                │
│    Step 4: Display configuration                                │
│    Step 5: Validate media lookup (if enabled)                   │
│           └→ Check media IDs exist in WordPress                 │
│    Step 6: Create backup directory                              │
│    Step 7: Determine which pages to update                      │
│           └→ Parse input (all/names/IDs)                        │
│    Step 8: Update pages via REST API                            │
│           ├→ DRY RUN: Show what would change                    │
│           └→ LIVE RUN: Apply changes                            │
│                ├→ Read content JSON                             │
│                ├→ Build Gutenberg blocks                        │
│                ├→ Backup existing page                          │
│                ├→ PATCH to WordPress                            │
│                └→ Save results                                  │
│    Step 9: Upload backup artifacts                              │
│    Step 10: Upload results artifacts                            │
│    Step 11: Generate summary report                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. Job: verify-updates (if not dry-run and success)             │
│    ✓ Check pages are accessible                                 │
│    ✓ Run verification scripts                                   │
│    ✓ Report accessibility status                                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. Results & Artifacts                                           │
│    ✓ Workflow summary in GitHub Actions                         │
│    ✓ Backups available as artifacts (30 days)                   │
│    ✓ Results and logs as artifacts (14 days)                    │
│    ✓ Pages updated on WordPress site                            │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌─────────────────┐
│  Content Files  │
│   (JSON format) │
│                 │
│  home.json      │
│  about.json     │
│  contact.json   │
└────────┬────────┘
         │
         ├────────────┐
         │            │
         ↓            ↓
┌─────────────┐  ┌──────────────────┐
│ Media Lookup│  │ Update Script    │
│   (JSON)    │→→│  update_pages.py │
│             │  │                  │
│ filename    │  │ 1. Parse JSON    │
│    ↓        │  │ 2. Map media     │
│ media_id    │  │ 3. Build blocks  │
└─────────────┘  │ 4. Build markup  │
                 └────────┬─────────┘
                          │
                          ↓
                 ┌─────────────────┐
                 │ WordPress REST  │
                 │      API        │
                 │                 │
                 │ GET /pages/:id  │←── Backup
                 │ PATCH /pages/:id│←── Update
                 └────────┬────────┘
                          │
         ┌────────────────┼────────────────┐
         ↓                ↓                ↓
┌────────────────┐ ┌──────────────┐ ┌──────────────┐
│  Backup JSON   │ │  WordPress   │ │  Result JSON │
│   (Artifact)   │ │   Database   │ │  (Artifact)  │
└────────────────┘ └──────────────┘ └──────────────┘
```

## Page Update Process Detail

```
┌──────────────────────────────────────────────────────────────┐
│ For Each Page in Content Directory:                          │
└──────────────────────────────────────────────────────────────┘
                       ↓
      ┌────────────────┴────────────────┐
      │                                  │
      ↓                                  ↓
┌─────────────┐                    ┌─────────────┐
│   DRY RUN   │                    │  LIVE RUN   │
└─────────────┘                    └─────────────┘
      ↓                                  ↓
┌─────────────────────┐            ┌─────────────────────┐
│ 1. Read JSON file   │            │ 1. Read JSON file   │
│ 2. Parse blocks     │            │ 2. Parse blocks     │
│ 3. Map media IDs    │            │ 3. Map media IDs    │
│ 4. Build markup     │            │ 4. Build markup     │
│ 5. DISPLAY output   │            │ 5. GET backup       │
│    (don't send)     │            │ 6. PATCH to WP      │
│ 6. Log would-update │            │ 7. Save backup      │
│                     │            │ 8. Save result      │
└─────────────────────┘            └─────────────────────┘
      ↓                                  ↓
┌─────────────────────┐            ┌─────────────────────┐
│ Output: Preview     │            │ Output: Updated     │
│ - List of pages     │            │ - Backups saved     │
│ - No changes made   │            │ - Pages updated     │
│ - Validation report │            │ - Results logged    │
└─────────────────────┘            └─────────────────────┘
```

## Security & Access Control

```
┌───────────────────────────────────────────────────────────┐
│                    GitHub Secrets                          │
│  (Encrypted, never exposed in logs)                       │
│                                                            │
│  ┌──────────────────────────────────────────────────┐    │
│  │ WP_USERNAME        → WordPress admin username     │    │
│  │ WP_APP_PASSWORD    → Application Password         │    │
│  │ WP_SITE_URL        → Site URL                     │    │
│  └──────────────────────────────────────────────────┘    │
└────────────────────────────┬──────────────────────────────┘
                             │
                             ↓ (Injected as env vars)
┌────────────────────────────────────────────────────────────┐
│                  GitHub Actions Runner                      │
│                  (Ephemeral environment)                    │
│                                                             │
│  Environment Variables:                                     │
│  - WP_USERNAME                                              │
│  - WP_APP_PASSWORD                                          │
│  - WP_SITE_URL                                              │
└────────────────────────────┬───────────────────────────────┘
                             │
                             ↓ (HTTPS)
┌────────────────────────────────────────────────────────────┐
│              WordPress REST API                             │
│           (Basic Auth with App Password)                    │
│                                                             │
│  Authorization: Basic base64(username:app_password)         │
└─────────────────────────────────────────────────────────────┘
```

## Artifact Storage & Lifecycle

```
┌──────────────────────────────────────────────────────────────┐
│                  Workflow Execution                          │
└───────────────────────────┬──────────────────────────────────┘
                            │
         ┌──────────────────┼──────────────────┐
         ↓                  ↓                  ↓
┌─────────────────┐  ┌─────────────┐  ┌──────────────────┐
│  Backup Files   │  │ Result Logs │  │  Validation      │
│  (JSON format)  │  │  (JSON/txt) │  │  Reports (JSON)  │
└─────────────────┘  └─────────────┘  └──────────────────┘
         ↓                  ↓                  ↓
┌─────────────────────────────────────────────────────────────┐
│              GitHub Actions Artifacts                        │
│                                                              │
│  Artifact Name: page-backups-{run_number}                   │
│  Retention: 30 days                                          │
│  Contents: Original page JSON before updates                │
│                                                              │
│  Artifact Name: update-results-{run_number}                 │
│  Retention: 14 days                                          │
│  Contents: API responses, logs, validation reports          │
└─────────────────────────────────────────────────────────────┘
```

## Decision Flow for Users

```
                    START: Want to update pages?
                                 ↓
                        First time running?
                        /              \
                      YES               NO
                       ↓                 ↓
              Use dry_run: true    Know what you're doing?
                       ↓                 ↓
              Review preview      Choose run mode:
                       ↓                 ↓
              Safe? Acceptable?   ┌─────┴─────┐
                    /  \          ↓           ↓
                  YES   NO    dry_run:    dry_run:
                   ↓     ↓     true        false
           Set dry_run   Modify     ↓           ↓
           to false      content   Preview   Apply changes
                   ↓         ↓         ↓           ↓
                Run again  ←─┘    Download  Download
                   ↓               artifacts artifacts
              Apply changes            ↓           ↓
                   ↓               No changes  Changes applied
              Download backups         made    to WordPress
                   ↓                   ↓           ↓
              Verify on site      Try again?   Verify on site
                   ↓                   ↓           ↓
            ┌──────┴──────┐         ←─┘      Success? Keep?
            ↓             ↓                   /         \
        Success?      Problems?            YES          NO
            ↓             ↓                  ↓           ↓
         Keep it     Rollback from      Document    Restore
                       backups                     from backup
                                                       ↓
                                                   Try again
```

## Integration Points

```
┌────────────────────────────────────────────────────────────────┐
│                External System Integration                      │
└────────────────────────────────────────────────────────────────┘

WordPress REST API
       ↑
       │ HTTPS (Basic Auth)
       │
┌──────┴────────┐
│  Workflow     │
│  (This)       │
└───────┬───────┘
        │
        ├────→ GitHub Secrets (Read credentials)
        │
        ├────→ Repository Content (Read JSON files)
        │
        ├────→ GitHub Artifacts (Write backups/results)
        │
        └────→ GitHub Actions Logs (Write status/errors)
```

## Monitoring & Observability

```
┌─────────────────────────────────────────────────────────┐
│            Where to Find Information                     │
└─────────────────────────────────────────────────────────┘

GitHub Actions Tab
    ↓
Workflow Run Page
    ↓
├─ Summary Page
│  └─ Overall status, duration, triggered by
│
├─ Jobs View
│  ├─ validate-prerequisites
│  ├─ update-pages
│  └─ verify-updates
│
├─ Logs (Per Step)
│  ├─ Setup logs
│  ├─ Execution logs
│  ├─ Error messages
│  └─ Debug output
│
└─ Artifacts
   ├─ page-backups-XXX (ZIP)
   │  └─ Original page JSON files
   │
   └─ update-results-XXX (ZIP)
      ├─ API responses
      ├─ Update logs
      └─ Validation reports
```

## Error Handling Flow

```
┌────────────────────────────────────────┐
│   Workflow Encounters Error             │
└─────────────────┬──────────────────────┘
                  │
    ┌─────────────┼─────────────┐
    ↓             ↓             ↓
Missing       Auth Error    Media Error
Secrets          │              │
    │            │              │
    ↓            ↓              ↓
Fail fast   Stop workflow   Show warning
Show msg    Show error      Continue
Exit        Exit            (if allowed)
    │            │              │
    └────────────┴──────────────┘
                  ↓
    ┌─────────────────────────────┐
    │  All Errors Logged To:      │
    │  1. GitHub Actions Logs     │
    │  2. Step Output             │
    │  3. Workflow Summary        │
    │  4. Artifacts (if created)  │
    └─────────────────────────────┘
```

---

## Legend

- `→` : Data flow
- `↓` : Process flow
- `├─` : Branch/option
- `┌─┐` : Process/component box
- `✓` : Completed step
- `×` : Failed/skipped step

---

**For detailed implementation, see:**
- `update-pages-manual.yml` - Workflow definition
- `UPDATE_PAGES_WORKFLOW_GUIDE.md` - Complete guide
- `QUICK_REFERENCE.md` - Quick start guide
