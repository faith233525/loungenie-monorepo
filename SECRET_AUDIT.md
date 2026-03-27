# Secret Usage Audit — GitHub Actions Workflows

Generated: 2026-03-27  
Scope: `.github/workflows/*.yml` (15 workflow files)

---

## Summary

| Category | Unique secret names | Workflows using them |
|---|---|---|
| WordPress | 8 | 7 |
| FTP | 6 | 10 |
| cPanel | 5 | 7 |
| Staging env | 8 | 3 |
| Production env | 4 | 1 |
| SSL certificate | 3 | 1 |
| GitHub (built-in) | 1 | 2 |
| **Total** | **35** | **15** |

**⚠️ 3 naming inconsistencies found** — see [Issues & Recommendations](#issues--recommendations).

---

## Secret-to-Workflow Matrix

### WordPress Secrets

| Secret Name | apply-site-staging | auto-deploy-staging | automated-staging-deploy | deploy-cpanel | deploy-with-secrets | dry_run_sync | test-connections | validate-secrets |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `WP_SITE_URL` | ✅ | ✅ | | ✅ | ✅ | | ✅ | ✅ |
| `WP_REST_USER` | ✅ | ✅ | | ✅ | ✅ | | | ✅ |
| `WP_REST_PASS` | ✅ | ✅ | | ✅ | ✅ | | | ✅ |
| `WP_REST_USERNAME` | | | | | ✅ | | ✅ | ✅ |
| `WP_REST_PASSWORD` | | | | | ✅ | | ✅ | ✅ |
| `WP_URL` | | | ✅ | | | ✅ | | ✅ |
| `WP_USER` | | | ✅ | | | ✅ | | ✅ |
| `WP_APP_PASS` | | | ✅ | | | ✅ | | ✅ |

> **⚠️ Inconsistency #1 (WordPress):** Two parallel naming conventions exist:
> - `WP_REST_USER` / `WP_REST_PASS` / `WP_SITE_URL` — used by apply-site, auto-deploy, deploy-cpanel
> - `WP_REST_USERNAME` / `WP_REST_PASSWORD` — used by test-connections
> - `WP_USER` / `WP_APP_PASS` / `WP_URL` — used by automated-staging-deploy, dry_run_sync  
>
> `deploy-with-secrets.yml` bridges both with fallback logic (`WP_REST_USER` → `WP_REST_USERNAME`).

---

### FTP Secrets

| Secret Name | auto-deploy-staging | automated-staging-deploy | deploy-cpanel | deploy-portal | deploy-staging-ftp | deploy-with-secrets | dry_run_sync | remove-portal-staging | ssl-install | test-connections | validate-secrets |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `FTP_HOST` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `FTP_USER` | ✅ | ✅ | ✅ | ✅ | | ✅ | ✅ | | | | ✅ |
| `FTP_PASS` | ✅ | ✅ | ✅ | ✅ | | ✅ | ✅ | | | | ✅ |
| `FTP_USERNAME` | | | | | ✅ | ✅ | | ✅ | ✅ | ✅ | ✅ |
| `FTP_PASSWORD` | | | | | ✅ | ✅ | | ✅ | ✅ | ✅ | ✅ |
| `FTP_PORT` | | | | | ✅ | | | ✅ | | | |

> **⚠️ Inconsistency #2 (FTP credentials):** Two naming conventions exist for the same credentials:
> - `FTP_USER` / `FTP_PASS` — used by auto-deploy-staging, automated-staging-deploy, deploy-portal, dry_run_sync
> - `FTP_USERNAME` / `FTP_PASSWORD` — used by deploy-staging-ftp, remove-portal-staging, ssl-install, test-connections  
>
> `deploy-with-secrets.yml` bridges both with fallback logic (`FTP_USER` → `FTP_USERNAME`, `FTP_PASS` → `FTP_PASSWORD`).  
> **Recommendation:** Standardize on `FTP_USERNAME` / `FTP_PASSWORD` (matches test-connections and the standalone FTP workflows).

---

### cPanel Secrets

| Secret Name | apply-site-staging | cpanel-pull-deploy | deploy-cpanel | deploy-with-secrets | ssl-install | test-connections | validate-secrets |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `CPANEL_HOST` | ✅ | ✅ | ✅ | | ✅ | ✅ | ✅ |
| `CPANEL_USER` | | ✅ | ✅ | | ✅ | ✅ | ✅ |
| `CPANEL_API_TOKEN` | ✅ | ✅ | ✅ | ✅ | | ✅ | ✅ |
| `CPANEL_TOKEN` | | | | ✅ | ✅ | | ✅ |
| `CPANEL_REPO` | | ✅ | ✅ | | | | ✅ |

> **⚠️ Inconsistency #3 (cPanel token):** Two names for the cPanel API token:
> - `CPANEL_API_TOKEN` — used by apply-site-staging, cpanel-pull-deploy, deploy-cpanel, test-connections
> - `CPANEL_TOKEN` — used by auto-deploy-staging, ssl-install  
>
> `deploy-with-secrets.yml` bridges both with fallback logic (`CPANEL_TOKEN` → `CPANEL_API_TOKEN`).  
> **Recommendation:** Standardize on `CPANEL_API_TOKEN` (most widely used) and update ssl-install.yml and auto-deploy-staging.yml.

---

### Staging Environment Secrets

| Secret Name | automated-staging-deploy | deploy-cpanel | deploy-portal | validate-secrets |
|---|:---:|:---:|:---:|:---:|
| `STAGING_URL` | ✅ | ✅ | ✅ | ✅ |
| `STAGING_PATH` | | ✅ | | ✅ |
| `STAGING_HOST` | | | | ✅ |
| `STAGING_USER` | | | | ✅ |
| `STAGING_WP_URL` | | | | ✅ |
| `STAGING_WP_USER` | | | | ✅ |
| `STAGING_WP_APP_PASSWORD` | | | | ✅ |
| `STAGING_SSH_KEY` | | | | ✅ |

> `STAGING_HOST`, `STAGING_WP_URL`, `STAGING_USER`, `STAGING_WP_USER`, `STAGING_WP_APP_PASSWORD`, and `STAGING_SSH_KEY` are declared in `validate-secrets.yml` but not used in any other active workflow. These secrets are validated speculatively for future SSH-based deployment.

---

### Production Environment Secrets

| Secret Name | validate-secrets |
|---|:---:|
| `PRODUCTION_HOST` | ✅ |
| `PRODUCTION_USER` | ✅ |
| `PRODUCTION_SSH_KEY` | ✅ |
| `PRODUCTION_PATH` | ✅ |

> All four production secrets are validated speculatively by `validate-secrets.yml` but no production deployment workflow currently uses them.

---

### SSL Certificate Secrets

| Secret Name | ssl-install |
|---|:---:|
| `CRT` | ✅ |
| `KEY` | ✅ |
| `CABUNDLE` | ✅ |

> These secrets are scoped exclusively to `ssl-install.yml`. They hold PEM-encoded certificate material and should only be set when running certificate renewal.

---

### Built-in GitHub Secrets

| Secret Name | auto-build-and-pr | auto-merge-deploy-pr |
|---|:---:|:---:|
| `GITHUB_TOKEN` | ✅ | ✅ |

> `GITHUB_TOKEN` is automatically provided by GitHub Actions for every workflow run. No manual secret configuration is required.

---

## Workflow-to-Secret Matrix

| Workflow | Secrets used |
|---|---|
| `apply-site-staging.yml` | `WP_SITE_URL`, `WP_REST_USER`, `WP_REST_PASS`, `CPANEL_HOST`, `CPANEL_API_TOKEN` |
| `auto-build-and-pr.yml` | `GITHUB_TOKEN` (built-in) |
| `auto-deploy-staging.yml` | `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `CPANEL_TOKEN`, `WP_REST_USER`, `WP_REST_PASS`, `WP_SITE_URL` |
| `auto-merge-deploy-pr.yml` | `GITHUB_TOKEN` (built-in) |
| `automated-staging-deploy.yml` | `WP_URL`, `WP_USER`, `WP_APP_PASS`, `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `STAGING_URL` |
| `cpanel-pull-deploy.yml` | `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_API_TOKEN`, `CPANEL_REPO` |
| `deploy-cpanel.yml` | `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_API_TOKEN`, `CPANEL_REPO`, `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `STAGING_PATH`, `WP_SITE_URL`, `WP_REST_USER`, `WP_REST_PASS`, `STAGING_URL` |
| `deploy-portal.yml` | `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `STAGING_URL` |
| `deploy-staging-ftp.yml` | `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_PORT` |
| `deploy-with-secrets.yml` | `CPANEL_TOKEN`, `CPANEL_API_TOKEN`, `FTP_HOST`, `FTP_USER`, `FTP_USERNAME`, `FTP_PASS`, `FTP_PASSWORD`, `WP_REST_USER`, `WP_REST_USERNAME`, `WP_REST_PASS`, `WP_REST_PASSWORD`, `WP_SITE_URL` |
| `dry_run_sync.yml` | `WP_URL`, `WP_USER`, `WP_APP_PASS`, `FTP_HOST`, `FTP_USER`, `FTP_PASS` |
| `remove-portal-staging.yml` | `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_PORT` |
| `ssl-install.yml` | `CRT`, `KEY`, `CABUNDLE`, `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_TOKEN`, `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD` |
| `test-connections.yml` | `WP_SITE_URL`, `WP_REST_USERNAME`, `WP_REST_PASSWORD`, `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`, `CPANEL_API_TOKEN`, `CPANEL_USER`, `CPANEL_HOST` |
| `validate-secrets.yml` | All 35 secrets listed above |

---

## Issues & Recommendations

### Issue 1 — FTP credential name inconsistency (HIGH)

**Problem:** Workflows split between `FTP_USER`/`FTP_PASS` and `FTP_USERNAME`/`FTP_PASSWORD` for the same FTP account.

**Affected workflows using `FTP_USER`/`FTP_PASS`:**
- `auto-deploy-staging.yml`
- `automated-staging-deploy.yml`
- `deploy-portal.yml`
- `deploy-cpanel.yml`
- `dry_run_sync.yml`

**Affected workflows using `FTP_USERNAME`/`FTP_PASSWORD`:**
- `deploy-staging-ftp.yml`
- `remove-portal-staging.yml`
- `ssl-install.yml`
- `test-connections.yml`

**Recommendation:** Standardize all workflows to `FTP_USERNAME` / `FTP_PASSWORD`. Update the five workflows using the old names, then remove the old secret from GitHub repository settings.

---

### Issue 2 — WordPress credential name inconsistency (MEDIUM)

**Problem:** Three sets of WordPress credential names across workflows:
- `WP_USER` / `WP_APP_PASS` / `WP_URL` (automated-staging-deploy, dry_run_sync)
- `WP_REST_USER` / `WP_REST_PASS` / `WP_SITE_URL` (apply-site-staging, auto-deploy-staging, deploy-cpanel)
- `WP_REST_USERNAME` / `WP_REST_PASSWORD` (test-connections)

**Recommendation:** Standardize on `WP_REST_USERNAME` / `WP_REST_PASSWORD` / `WP_SITE_URL` and migrate the older conventions.

---

### Issue 3 — cPanel token name inconsistency (MEDIUM)

**Problem:** `CPANEL_API_TOKEN` and `CPANEL_TOKEN` are both used for the same cPanel API token.

**Affected workflows using `CPANEL_TOKEN`:**
- `auto-deploy-staging.yml`
- `ssl-install.yml`

**All other cPanel workflows use:** `CPANEL_API_TOKEN`

**Recommendation:** Standardize on `CPANEL_API_TOKEN`. Update `auto-deploy-staging.yml` and `ssl-install.yml`.

---

### Issue 4 — `--insecure` curl flag in test-connections.yml (SECURITY)

**Problem:** `test-connections.yml` line 83 passes `-k` to curl when testing the cPanel API endpoint, bypassing SSL certificate verification. This defeats the purpose of TLS and could allow a MITM attack.

```yaml
# line 83 — current (insecure)
if curl -fsS -k -H "Authorization: cpanel ${CPANEL_USER}:${CPANEL_API_TOKEN}" \
    "https://${CPANEL_HOST}:2083/execute/Version/version" -o /dev/null; then
```

**Fix:** Remove the `-k` flag. Ensure `CPANEL_HOST` is set to the hostname `cpanel.loungenie.com` (not an IP address) so the GlobalSign certificate validates correctly.

**Status:** ✅ Fixed — `-k` removed from `test-connections.yml` line 83. Ensure `CPANEL_HOST` secret is set to `cpanel.loungenie.com` (hostname), not an IP address.

---

### Issue 5 — Secrets embedded inline in shell script (SECURITY)

**Problem:** `deploy-portal.yml` lines 51-52 interpolate `${{ secrets.STAGING_URL }}` directly into a shell `run` block. If the secret value contains shell metacharacters (e.g., `$(...)`, `` ` ``, `\n`), it could lead to command injection.

```yaml
# deploy-portal.yml lines 51-52 — risky pattern
if [ -n "${{ secrets.STAGING_URL }}" ]; then
  url="${{ secrets.STAGING_URL }}/${{ env.TARGET_DIR }}/wp-admin/install.php"
```

**Recommendation:** Pass secrets as environment variables and reference them via `$ENV_VAR` in the shell script, not via `${{ secrets.* }}` substitution.

---

## Required Secrets Reference

The minimum set of secrets that must be configured for all workflows to function:

| Secret | Purpose | Required by |
|---|---|---|
| `FTP_HOST` | FTP server hostname | 10 workflows |
| `FTP_USERNAME` | FTP login username | deploy-staging-ftp, remove-portal-staging, ssl-install, test-connections |
| `FTP_PASSWORD` | FTP login password | deploy-staging-ftp, remove-portal-staging, ssl-install, test-connections |
| `FTP_PORT` | FTP port (usually 21) | deploy-staging-ftp, remove-portal-staging |
| `FTP_USER` | FTP username (legacy name) | auto-deploy-staging, automated-staging-deploy, deploy-portal, deploy-cpanel, dry_run_sync |
| `FTP_PASS` | FTP password (legacy name) | auto-deploy-staging, automated-staging-deploy, deploy-portal, deploy-cpanel, dry_run_sync |
| `CPANEL_HOST` | cPanel hostname (`cpanel.loungenie.com`) | 6 workflows |
| `CPANEL_USER` | cPanel username | 5 workflows |
| `CPANEL_API_TOKEN` | cPanel API token | 5 workflows |
| `CPANEL_TOKEN` | cPanel API token (legacy name) | auto-deploy-staging, ssl-install |
| `CPANEL_REPO` | cPanel Git repo identifier | cpanel-pull-deploy, deploy-cpanel |
| `WP_SITE_URL` | WordPress staging site URL | 5 workflows |
| `WP_REST_USER` | WP REST API username | apply-site-staging, auto-deploy-staging, deploy-cpanel |
| `WP_REST_PASS` | WP REST API application password | apply-site-staging, auto-deploy-staging, deploy-cpanel |
| `WP_REST_USERNAME` | WP REST API username (alt name) | test-connections |
| `WP_REST_PASSWORD` | WP REST API app password (alt name) | test-connections |
| `WP_URL` | WordPress URL (alt name) | automated-staging-deploy, dry_run_sync |
| `WP_USER` | WordPress username (alt name) | automated-staging-deploy, dry_run_sync |
| `WP_APP_PASS` | WordPress app password (alt name) | automated-staging-deploy, dry_run_sync |
| `STAGING_URL` | Full staging site URL for smoke checks | 3 workflows |
| `STAGING_PATH` | Server path to staging installation | deploy-cpanel |
| `CRT` | SSL certificate PEM (renewal only) | ssl-install |
| `KEY` | SSL private key PEM (renewal only) | ssl-install |
| `CABUNDLE` | SSL CA bundle PEM (renewal only) | ssl-install |

> Secrets marked "(legacy name)" can be removed once the corresponding workflows are updated to use the standardized name.
