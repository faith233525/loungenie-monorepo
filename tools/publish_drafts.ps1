# Publish all pages currently in 'draft' status using stored WP credentials
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

$pagesUri = "$env:WP_SITE_URL/wp-json/wp/v2/pages?per_page=100"
try { $pages = Invoke-RestMethod -Uri $pagesUri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop } catch { Write-Error "Failed to fetch pages: $($_.Exception.Message)"; exit 1 }

$drafts = $pages | Where-Object { $_.status -eq 'draft' }
if (-not $drafts) { Write-Output "No draft pages to publish."; exit 0 }

foreach ($d in $drafts) {
    Write-Output "Publishing page $($d.id) - $($d.slug)"
    $body = @{ status = 'publish' } | ConvertTo-Json -Depth 4
    try {
        $resp = Invoke-RestMethod -Uri "$env:WP_SITE_URL/wp-json/wp/v2/pages/$($d.id)" -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
        Write-Output "Published: $($resp.id) -> status: $($resp.status)"
    } catch { Write-Error "Failed to publish $($d.id): $($_.Exception.Message)" }
}

Write-Output "Publish step complete. Review live site to verify layout and assets." 
