# Prepend the header block to all pages (except Home 4701) as drafts for review
$headerPath = Join-Path (Get-Location) 'tools\header_block.html'
if (-not (Test-Path $headerPath)) { Write-Error "Header block not found at $headerPath"; exit 2 }
$header = Get-Content -Raw $headerPath

# Load stored WP credentials
$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No wp_cred.xml at $wpFile"; exit 2 }
try { $wpCred = Import-Clixml -Path $wpFile } catch { Write-Error "Import-Clixml failed: $($_.Exception.Message)"; exit 1 }
function SecureStringToPlainText([System.Security.SecureString] $s){ $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try{ [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) } }
$user = $wpCred.UserName
$pass = SecureStringToPlainText $wpCred.Password
$pair = "{0}:{1}" -f $user,$pass
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))

if (-not $env:WP_SITE_URL) { $env:WP_SITE_URL = 'https://loungenie.com/staging' }

# Fetch pages list
$pagesUri = "$env:WP_SITE_URL/wp-json/wp/v2/pages?per_page=100"
try { $pages = Invoke-RestMethod -Uri $pagesUri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop } catch { Write-Error "Failed to fetch pages: $($_.Exception.Message)"; exit 1 }

foreach ($p in $pages) {
    if ($p.id -eq 4701) { Write-Output "Skipping Home (4701)"; continue }
    Write-Output "Preparing draft for page $($p.id) - $($p.slug)"
    $current = Invoke-RestMethod -Uri "$env:WP_SITE_URL/wp-json/wp/v2/pages/$($p.id)" -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    $newContent = $header + "`n`n" + $current.content.raw
    $body = @{ content = $newContent; status = 'draft' } | ConvertTo-Json -Depth 10
    try {
        $resp = Invoke-RestMethod -Uri "$env:WP_SITE_URL/wp-json/wp/v2/pages/$($p.id)" -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
        Write-Output "Updated page $($p.id) -> draft"
    } catch { Write-Error "Failed to update page $($p.id): $($_.Exception.Message)" }
}

Write-Output "Header prepend complete. All updated pages are now drafts. Review in WP admin before publishing."
