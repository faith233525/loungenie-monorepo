<#
Import stored WP credential from ~/.config/loungenie/wp_cred.xml and run apply_page_4862.ps1
#>

$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) {
    Write-Error "No stored WP credential found at $wpFile"
    exit 2
}

try {
    $wpCred = Import-Clixml -Path $wpFile
} catch {
    Write-Error "Failed to import WP cred: $($_.Exception.Message)"
    exit 1
}

function SecureStringToPlainText([System.Security.SecureString] $s) {
    $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
    try { [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
}

$env:WP_USER = $wpCred.UserName
$env:WP_PASS = SecureStringToPlainText $wpCred.Password
$env:WP_SITE_URL = 'https://loungenie.com/staging'

Write-Output "Running apply_page_4862.ps1 with WP user $($env:WP_USER) against $($env:WP_SITE_URL)"
try {
    & .\tools\apply_page_4862.ps1
} catch {
    Write-Error "apply_page_4862.ps1 failed: $($_.Exception.Message)"
    exit 1
}
