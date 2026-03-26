Deployment troubleshooting notes

- FTP `553 File name not allowed` typically indicates server-side filename or path restrictions. Workarounds:
  - Upload the plugin ZIP via WP Admin (Plugins → Add New → Upload Plugin) on the staging site.
  - Use cPanel API upload if you have a token (script: `scripts/upload_plugin_via_cpanel.ps1`).
  - Adjust FTP uploader to use passive mode or different remote path if the provider requires it.

- LiteSpeed `wp-json/litespeed/v1/notify_ccss` returning 401/forbidden means the REST user lacks permission. Actions:
  - Ensure the REST user is an administrator and the correct username/password are used.
  - Alternatively, clear/purge caches via hosting control panel if available.

Notes from recent automation attempts:
- FTP uploader attempted twice (standard WebClient and passive-mode FtpWebRequest fallback). Both attempts failed with 553 "File name not allowed" from the server.
- LiteSpeed notify attempts returned `rest_forbidden` (401). Confirm the REST user has `manage_options` capability or use an admin account for notify calls.


- Local deploy helper: `scripts/run-local-deploy.ps1` runs upload → notify → audit interactively.
