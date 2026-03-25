# Safe test: attempt a minimal POST update to page 4862 using stored creds
$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No cred file at $wpFile"; exit 2 }

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
$uri = "$env:WP_SITE_URL/wp-json/wp/v2/pages/4862"

Write-Output "Attempting safe POST update to $uri as $user"

# Minimal payload: set excerpt to current value (no-op) to test write permission
try {
    $current = Invoke-RestMethod -Uri $uri -Method Get -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    $excerpt = $current.excerpt.rendered
} catch {
    Write-Error "Failed to fetch current page: $($_.Exception.Message)"; exit 1
}

$payload = @{ excerpt = $excerpt } | ConvertTo-Json -Depth 6
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $payload -ErrorAction Stop
    Write-Output "Update response:"
    $resp | ConvertTo-Json -Depth 6 | Write-Output
} catch {
    Write-Error "Update failed: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        try { $code = $_.Exception.Response.StatusCode.value__; Write-Output "StatusCode: $code" } catch {}
        try { $stream = $_.Exception.Response.GetResponseStream(); $sr = New-Object System.IO.StreamReader($stream); $body = $sr.ReadToEnd(); Write-Output "Body: $body" } catch {}
    }
    exit 1
}
