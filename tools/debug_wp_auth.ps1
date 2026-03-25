# Debug WP Basic auth against page 4862 using stored wp_cred.xml
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

Write-Output "Using user: $user"
Write-Output "Requesting: $uri"

try {
    $resp = Invoke-WebRequest -Uri $uri -Method Get -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    Write-Output "HTTP Status: $($resp.StatusCode)"
    $resp.Content | ConvertFrom-Json | ConvertTo-Json -Depth 6 | Write-Output
} catch {
    Write-Error "Request failed: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        try {
            $code = $_.Exception.Response.StatusCode.value__
            Write-Output "StatusCode: $code"
            $stream = $_.Exception.Response.GetResponseStream()
            $sr = New-Object System.IO.StreamReader($stream)
            $body = $sr.ReadToEnd()
            Write-Output "Body: $body"
        } catch {
            Write-Output "No response body available."
        }
    }
    exit 1
}
