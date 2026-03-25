# Read the section templates, concatenate, and apply to page 4701 as a draft using stored creds
$sectionDir = Join-Path (Get-Location) 'tools\home_kadence_sections'
$files = Get-ChildItem -Path $sectionDir -Filter '*.html' | Sort-Object Name
if (-not $files) { Write-Error "No section files found in $sectionDir"; exit 2 }

$content = ''
foreach ($f in $files) { $content += Get-Content -Raw $f; $content += "`n`n" }

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
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/4701"

$body = @{ content = $content; status = 'draft' } | ConvertTo-Json -Depth 10

Write-Output "Applying concatenated sections as a draft to page 4701..."
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
    Write-Output "Applied draft -> id: $($resp.id) status: $($resp.status)"
} catch { Write-Error "Failed to apply sections: $($_.Exception.Message)"; if ($_.Exception.Response) { try { $code=$_.Exception.Response.StatusCode.value__; Write-Output "StatusCode: $code" } catch {} }; exit 1 }
