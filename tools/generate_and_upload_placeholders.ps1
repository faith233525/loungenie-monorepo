# Generate SVG placeholders from tools/image_inventory.csv and upload to WP media via REST
Set-StrictMode -Version Latest
$csv = 'tools\image_inventory.csv'
if (-not (Test-Path $csv)) { Write-Error "No image inventory at $csv"; exit 2 }

$outDir = 'tools\images_to_upload'
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

$rows = Import-Csv -Path $csv
if (-not $rows) { Write-Error 'No rows in CSV'; exit 1 }

# Load WP creds
$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
$wpFile = Join-Path $credDir 'wp_cred.xml'
if (-not (Test-Path $wpFile)) { Write-Error "No wp_cred.xml at $wpFile"; exit 2 }
try { $wpCred = Import-Clixml -Path $wpFile } catch { Write-Error "Import-Clixml failed: $($_.Exception.Message)"; exit 1 }
function SecureStringToPlainText([System.Security.SecureString] $s){ $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try{ [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally {[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b)} }
$user=$wpCred.UserName; $pass=SecureStringToPlainText $wpCred.Password
$pair = "{0}:{1}" -f $user,$pass
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
if (-not $env:WP_SITE_URL) { $env:WP_SITE_URL = 'https://loungenie.com/staging' }
$mediaUri = "$env:WP_SITE_URL/wp-json/wp/v2/media"

$results = @()
foreach ($r in $rows) {
    $path = $r.path.Trim()
    if (-not $path) { continue }
    $filename = [System.IO.Path]::GetFileName($path)
    $localPath = Join-Path $outDir $filename
    # Generate simple SVG placeholder
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='1200' height='800'><rect width='100%' height='100%' fill='#cfe6ff'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='48' fill='#004080'>Placeholder`n$filename</text></svg>"
    $svg | Out-File -FilePath $localPath -Encoding utf8
    Write-Output "Generated placeholder: $localPath"

    # Attempt upload
    try {
        $bytes = [System.IO.File]::ReadAllBytes($localPath)
        $headers = @{ Authorization = "Basic $auth"; 'Content-Disposition' = "attachment; filename=`"$filename`"" }
        $resp = Invoke-RestMethod -Uri $mediaUri -Method Post -Headers $headers -Body $bytes -ContentType 'image/svg+xml' -ErrorAction Stop
        Write-Output "Uploaded: $filename -> $($resp.source_url)"
        $results += [PSCustomObject]@{ file = $filename; url = $resp.source_url; id = $resp.id }
    } catch {
        Write-Error ("Upload failed for {0}: {1}" -f $filename, $_.Exception.Message)
    }
}

if ($results.Count -gt 0) {
    $results | ConvertTo-Json -Depth 5 | Out-File -FilePath 'tools/uploaded_placeholders.json' -Encoding utf8
    Write-Output "Saved upload results to tools/uploaded_placeholders.json"
} else { Write-Output 'No uploads succeeded.' }
