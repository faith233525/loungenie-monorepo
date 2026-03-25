# Backup page 4701 (Home) to tools/backup_home_4701.json using stored WP creds
$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No wp_cred.xml at $wpFile"; exit 2 }

try { $wpCred = Import-Clixml -Path $wpFile } catch { Write-Error "Import-Clixml failed: $($_.Exception.Message)"; exit 1 }

function SecureStringToPlainText([System.Security.SecureString] $s){
    $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
    try { [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
}

$user = $wpCred.UserName
$pass = SecureStringToPlainText $wpCred.Password
$pair = "{0}:{1}" -f $user, $pass
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))

if (-not $env:WP_SITE_URL) { $env:WP_SITE_URL = 'https://loungenie.com/staging' }
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/4701?context=edit"

try {
    $resp = Invoke-RestMethod -Uri $uri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    if (-not (Test-Path 'tools')) { New-Item -ItemType Directory -Path 'tools' | Out-Null }
    $resp | ConvertTo-Json -Depth 10 | Out-File -FilePath 'tools/backup_home_4701.json' -Encoding utf8
    Write-Output "Saved backup to tools/backup_home_4701.json"
} catch { Write-Error "Failed to backup page: $($_.Exception.Message)"; exit 1 }
