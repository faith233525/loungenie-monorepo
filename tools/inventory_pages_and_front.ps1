# Inventory pages and detect front page via settings (uses stored wp_cred.xml)
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
$settingsUri = "$env:WP_SITE_URL/wp-json/wp/v2/settings"

Write-Output "Fetching pages from: $pagesUri"
try {
    $pages = Invoke-RestMethod -Uri $pagesUri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
} catch { Write-Error "Failed to fetch pages: $($_.Exception.Message)"; exit 1 }

Write-Output "Fetched $($pages.Count) pages. Listing id,slug,title,status:" 
$pages | ForEach-Object { Write-Output ("{0}`t{1}`t{2}`t{3}" -f $_.id, $_.slug, ($_.title.rendered -replace '\s+', ' '), $_.status) }

Write-Output "Fetching site settings to detect front page (page_on_front)"
try {
    $settings = Invoke-RestMethod -Uri $settingsUri -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
    if ($settings.page_on_front -and $settings.page_on_front -ne 0) {
        Write-Output "Detected front page ID: $($settings.page_on_front)"
        $front = $pages | Where-Object { $_.id -eq $settings.page_on_front }
        if ($front) { Write-Output "Front page slug/title: $($front.slug) / $($front.title.rendered)" }
    } else {
        Write-Output "No front page set in settings. Trying common slugs: home, homepage, front-page"
        $candidates = $pages | Where-Object { $_.slug -in @('home','homepage','front-page') }
        if ($candidates) { $candidates | ForEach-Object { Write-Output ("Candidate front: {0} {1}" -f $_.id,$_.slug) } }
    }
} catch { Write-Output "Could not fetch settings: $($_.Exception.Message)" }

Write-Output "Inventory complete. Save this output for planning edits."
