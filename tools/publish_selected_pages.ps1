# Publish a selected list of pages we updated previously
$ids = @(9259,9258,9257,9256,5716,5686,5668,5651,5285,5223,5139,4862,2989,4701)

$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No wp_cred.xml at $wpFile"; exit 2 }
try { $wpCred = Import-Clixml -Path $wpFile } catch { Write-Error "Import-Clixml failed: $($_.Exception.Message)"; exit 1 }
function SecureStringToPlainText([System.Security.SecureString] $s){ $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try{ [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) } }

$user=$wpCred.UserName
$pass=SecureStringToPlainText $wpCred.Password
$pair = "{0}:{1}" -f $user,$pass
$auth=[Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
if (-not $env:WP_SITE_URL) { $env:WP_SITE_URL = 'https://loungenie.com/staging' }

foreach ($id in $ids) {
    Write-Output "Publishing page $id"
    $uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/$id"
    $body = @{ status = 'publish' } | ConvertTo-Json -Depth 4
    try {
        $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
        Write-Output "Published $id -> status: $($resp.status)"
    } catch { Write-Error ("Failed to publish {0}: {1}" -f $id, $_.Exception.Message) }
}

Write-Output "Selected pages publish complete."
