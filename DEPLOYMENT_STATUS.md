# Deployment Status: Blue Gradient Design to Staging

## Current Status ✓ READY

Your page payloads are prepared and ready to deploy to staging WordPress. The blue gradient design with logo from `content/pages/home.html` is in the repo.

### What's Complete ✓
- ✓ Page HTML payloads created (11 pages in `content/pages/`)
- ✓ Blue gradient design verified in `content/pages/home.html`
- ✓ FTP plugins uploaded successfully (portal + mu-plugins)
- ✓ REST API sync script ready in `tools/apply_site_via_rest.py`
- ✓ Workflow `apply-site-staging.yml` created and tested

### What's Blocking ❌
The REST page-sync workflow failed because **WordPress REST API credentials are not set in GitHub Actions secrets**.

#### Missing Secrets (GitHub Actions → Settings → Secrets)

Add **exactly ONE** value for each of these three groups:

**Group 1 — WordPress Site URL:**
- `WP_SITE_URL` (recommended) OR
- `STAGING_WP_URL` OR
- `WP_URL`

Example: `https://loungenie.com/staging` or `https://staging.loungenie.com`

**Group 2 — WordPress Username:**
- `WP_REST_USER` (recommended) OR
- `STAGING_WP_USER` OR
- `WP_REST_USERNAME` OR
- `WP_USER`

Example: `copilot` or `admin`

**Group 3 — WordPress App Password:**
- `WP_REST_PASS` (recommended) OR
- `STAGING_WP_APP_PASSWORD` OR
- `WP_REST_PASSWORD` OR
- `WP_APP_PASS`

Example: `xxxx xxxx xxxx xxxx xxxx xxxx` (from WordPress user profile → Application Passwords)

---

## How to Deploy

### Step 1: Get Your WordPress Credentials

1. **WordPress Site URL:**
   - You already know the staging URL (e.g., `https://loungenie.com/stage`)

2. **WordPress Username:**
   - Log into your staging WordPress admin
   - Note the username you use to log in

3. **App Password (Recommended):**
   - In WordPress: Users → Your User → Scroll to "Application Passwords"
   - Generate a new app password named "GitHub Deploy"
   - Copy it (format: `xxxx xxxx xxxx xxxx xxxx xxxx`)

### Step 2: Set GitHub Actions Secrets

From your local terminal (in the repo folder), run:

```bash
# Set the WordPress URL
gh secret set WP_SITE_URL --body "https://loungenie.com/stage"

# Set the WordPress username
gh secret set WP_REST_USER --body "copilot"

# Set the app password
gh secret set WP_REST_PASS --body "xxxx xxxx xxxx xxxx xxxx xxxx"
```

Or set them manually:
1. Go to: GitHub → Your Repo → Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Add each of the three secrets above

### Step 3: Deploy

```bash
# Dispatch the workflow to sync pages to WordPress
gh workflow run apply-site-staging.yml
```

Or click: GitHub → Actions → "Apply Site Staging" → "Run workflow" → "Run workflow" button

---

## What Will Happen

When you complete the steps above and re-run the workflow:

1. **REST API connects** to your staging WordPress with the credentials
2. **Pages sync** — the 11 page payloads upload to WordPress
3. **Design goes live** — your blue gradient + logo appear on staging
4. **Verification** — workflow shows success/failure in logs

---

## Troubleshooting

**Error: "Missing STAGING_WP_URL / STAGING_WP_USER / STAGING_WP_APP_PASSWORD"**
→ One or more secrets are not set. Re-check Step 2 above.

**Error: "401 Unauthorized"**
→ The credentials are wrong. Verify the username and app password in WordPress.

**Error: "404 Not Found"**
→ The WordPress URL is wrong or the REST API is disabled. Check the URL is correct and REST API is accessible.

**Error: "SKIP home: page not found"**
→ The WordPress page with slug `home` doesn't exist. Create it first via WordPress admin, then re-run the workflow.

---

## Page Payloads Included

All 11 pages are ready to sync:

- `home.html` — Landing page with blue gradient hero ★ FEATURED
- `about.html`
- `contact.html`
- `features.html`
- `pricing.html`
- `investors.html`
- `press.html`
- `careers.html`
- `privacy.html`
- `terms.html`
- `sitemap.html`

Each file contains the full page HTML as stored in your repository.

---

## Questions?

Check:
- [DEPLOYING.md](DEPLOYING.md) — General deployment guide
- [.github/workflows/apply-site-staging.yml](.github/workflows/apply-site-staging.yml) — Workflow definition
- [tools/apply_site_via_rest.py](tools/apply_site_via_rest.py) — REST sync script
