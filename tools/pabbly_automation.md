Pabbly webhook → FTP automation (AI → HostPapa)
================================================

Overview
--------
This document describes a safe, staged workflow that accepts AI-generated code via a webhook
and uploads it to your HostPapa site using Pabbly Connect (or similar automation tool).
It performs a backup of the target file before replacing it and is intended for staging only.

High-level flow
---------------
1. AI (external assistant) issues HTTP POST to Pabbly webhook with a JSON payload describing:
   - target_plugin: slug (e.g., loungenie-block-patterns)
   - target_path: path inside plugin (e.g., /loun-genie-patterns/patterns/pricing.php)
   - action: upload_replace
   - content: base64-encoded file contents
   - author: friendly name
   - reason: short description

2. Pabbly workflow steps:
   - Trigger: Webhook (Catch Hook)
   - Action A: Validate payload (required fields, allowed plugin list)
   - Action B: FTP — MOVE existing file to a timestamped backup folder (e.g., /backups/plugins/slug/filename.YYYYMMDDHHMM.bak)
   - Action C: FTP — Upload the new file to the requested `target_path`
   - Action D: (Optional) Call a staging verification webhook or send Slack/email with results

Security & safety
-----------------
- Use staging only. Never point automation at production without manual review.
- Create a dedicated FTP account limited to `/public_html/loungenie/wp-content/plugins/<plugin-slug>`.
- In Pabbly, store credentials in Pabbly's secure connector settings (do NOT embed credentials in webhook).
- Whitelist the AI's IPs (if possible) or require a shared secret in the webhook header.
- Keep an immutable backup of the full site (files + DB) outside of this process.

Sample webhook payload (JSON)
-----------------------------
{
  "target_plugin": "loungenie-block-patterns",
  "target_path": "patterns/pricing.php",
  "action": "upload_replace",
  "content": "PD9waHAgLy8gU2FtcGxlIHBhdHRlcm4gUEMgY29udGVudA0K...",
  "author": "Copilot",
  "reason": "Add Pricing Table block pattern v1",
  "timestamp": "2026-03-25T12:34:56Z"
}

Notes about `content`: use base64 encoding to avoid JSON escaping issues. Pabbly can decode base64 via a formatter step before uploading.

Pabbly setup steps (concise)
----------------------------
1. Create a new workflow in Pabbly Connect.
2. Add Trigger: Webhook (Catch Hook). Save the generated webhook URL.
3. Add a Formatter step (Decode Base64) to convert `content` → file body.
4. Add a Filter/Condition to allow requests only when `target_plugin` is in an allowed list.
5. Add FTP step: Move/Rename existing file to `/backups/plugins/<slug>/filename.YYYYMMDDHHMM.bak`.
   - If the file does not exist, continue.
6. Add FTP step: Upload file to `/public_html/loungenie/wp-content/plugins/<slug>/<target_path>`.
7. Add Notification step: Slack/Email with status and link to staging page.

Testing checklist
-----------------
- Test webhook POST with a small text file to a `test.txt` path. Confirm backup created and upload works.
- Confirm that uploaded PHP files show up in the plugin folder and that WordPress recognizes them.
- Test restoring backup by moving the .bak file back into place.

Examples of AI prompts for common tasks
-------------------------------------
- Add block pattern (pricing table):
  "Write PHP `register_block_pattern` code for a Kadence-based pricing table using the LounGenie palette. Return only the raw PHP file contents."
- Update header filter in `LounGenie Theme Brain`:
  "Provide a small PHP function to filter the header background color. Wrap as a safe plugin-compatible snippet and include a short comment describing where to paste."
- SEO analysis (no file upload):
  Send content to AI, receive meta description and suggestions, paste into Gutenberg or Rank Math manually.

Rollback notes
--------------
- The workflow backs up the previous file. To rollback: use FTP to restore the .bak to the original filename, then clear caches.

Limitations
-----------
- This approach uploads files directly; it does NOT update plugin settings stored in the DB. Use the WordPress dashboard for DB changes.
- Avoid executing unknown PHP from untrusted AI output. Always review code in staging before enabling on production.

Want me to:
- (A) Produce a ready-to-send webhook test curl command and example base64-encoded file, or
- (B) Generate the exact Pabbly step-by-step UI instructions with screenshots mockups, or
- (C) Create a minimal local script that encodes a PHP snippet and POSTs to the Pabbly webhook for testing.
