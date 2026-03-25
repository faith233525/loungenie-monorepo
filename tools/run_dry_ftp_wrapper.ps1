$env:FTP_HOST = 'ftp.poolsafeinc.com'
$env:FTP_USER = 'copilot@loungenie.com'
$env:FTP_PASS = 'LounGenie21!'
$env:RUN_FTP_BACKUP = 'true'
$env:DRY_RUN = 'true'

# Invoke the safe orchestrator script (relative path)
& "$PSScriptRoot\run_wp_ftp_and_rest_safe.ps1"
