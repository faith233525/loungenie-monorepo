Secrets and CI configuration
============================

Required repository secrets for GitHub Actions (add under repo Settings → Secrets):

- `FTP_HOST` — FTP(S) hostname (e.g. ftp.example.com)
- `FTP_USER` — FTP username
- `FTP_PASS` — FTP password
 - `WP_USER`  — WordPress username (use app password with REST permissions)
 - `WP_PASS`  — WordPress application password
- `STAGING_URL` — Base URL for staging site (e.g. https://example.com/staging)
- `ALLOW_UNTRUSTED_FTP_CERT` — Optional. Set to `true` only if you explicitly trust a self-signed FTPS certificate. Default: leave unset or `false`.

FTP_TYPE
--------
Set `FTP_TYPE` in workflow environment to one of:

- `ftps` — enforce explicit FTPS (TLS). Fail if FTPS cannot connect.
- `ftp`  — plain FTP only.
- `auto` — try FTPS first, then fallback to FTP. (default behavior if not set)

Security guidance
-----------------
- Prefer `FTP_TYPE=ftps` for CI runs.
- Do NOT enable `ALLOW_UNTRUSTED_FTP_CERT` unless you control the server and explicitly accept the certificate risk.

CI run control
--------------
To control what the CI script runs, set the following env vars in the workflow or Secrets:

- `RUN_FTP_BACKUP` (true/false)
- `RUN_TEST_POST` (true/false) — avoid in CI unless you want posts created.
- `RUN_HEADER_POST` (true/false)
- `RUN_BULK_POST` (true/false)
- `RUN_AUDITS` (true/false)

Recommended canonical secret names (used by workflows):

- `FTP_HOST`
- `FTP_USER`
- `FTP_PASS` (or `SFTP_PRIVATE_KEY` for key-based SFTP)
- `FTP_TYPE` (ftps | ftp | sftp)
- `ALLOW_UNTRUSTED_FTP_CERT` (optional)
- `WP_USER`
- `WP_PASS`
- `STAGING_URL`

CI default safety flags set in workflows:

- `DRY_RUN=true` — script will avoid destructive publishes by default.
- `CREATE_DRAFT_ONLY=true` — create drafts instead of publishing.

Default CI behavior: `RUN_FTP_BACKUP=true`, `RUN_AUDITS=true`, other run flags default to `false`.

If you want me to add this to the main `README.md`, say "add to README".
