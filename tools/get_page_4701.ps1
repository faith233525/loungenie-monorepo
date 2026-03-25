# Fetch page 4701 and print status and modified timestamp
$credDir = Join-Path $env:USERPROFILE '.config\\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No wp_cred.xml at $wpFile"; exit 2 }
try { $wpCred = Import-Clixml -Path $wpFile } catch { Write-Error "Import-Clixml failed: $($_.Exception.Message)"; exit 1 }
function SecureStringToPlainText([System.Security.SecureString] $s){ $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try{ [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) } }
$user=$wpCred.UserName
$pass=SecureStringToPlainText $wpCred.Password
$pair = "{0}:{1}" -f $user,$pass
$auth=[Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
if (-not $env:WP_SITE_URL) { $env:WP_SITE_URL = 'https://loungenie.com/staging' }
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/4701"
try {
    $resp = Invoke-RestMethod -Uri $uri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    Write-Output "id: $($resp.id)  status: $($resp.status)  modified: $($resp.modified)"
} catch { Write-Error "Failed to fetch page 4701: $($_.Exception.Message)"; exit 1 }
