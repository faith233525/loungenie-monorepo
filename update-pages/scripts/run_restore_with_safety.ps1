<#
One-click restore script with safety prompts.

Usage:
  - Run interactively: `powershell -ExecutionPolicy Bypass -File .\scripts\run_restore_with_safety.ps1`
  - Or set env var first: `$env:WP_AUTH = "username:app-password"` then run the script.

What it does:
  1. Creates `outputs/backups/` and `outputs/post_results/`.
  2. Backs up current pages referenced by `artifacts\page_*_update.json`.
  3. Posts a test update for page 4862 and saves the result.
  4. Prompts to continue; on confirmation, POSTs all payloads and optional header/navigation payloads.
  5. Optionally runs `scripts\run_automated_audits.ps1` when finished.
#>

function Get-AuthHeader {
    if ($env:WP_AUTH -and $env:WP_AUTH.Trim() -ne "") {
        $auth = $env:WP_AUTH
    }
    else {
        $user = Read-Host "WP username"
        $secure = Read-Host "Application password (input is hidden)" -AsSecureString
        $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
        $pwd = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
        $auth = "$user`:$pwd"
    }
    $pair = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($auth))
    return @{ Authorization = "Basic $pair" }
}

try {
    $base = "https://loungenie.com/staging/wp-json/wp/v2/pages"

    New-Item -Path outputs\backups -ItemType Directory -Force | Out-Null
    New-Item -Path outputs\post_results -ItemType Directory -Force | Out-Null

    $hdr = Get-AuthHeader

    # Backup current pages referenced by artifacts
    Write-Host "Backing up pages referenced by artifacts/*.json..."
    $payloadFiles = Get-ChildItem -Path artifacts\page_*_update.json -ErrorAction SilentlyContinue
    if (-not $payloadFiles) {
        Write-Host "No payload files found in artifacts/. Exiting." -ForegroundColor Yellow
        exit 1
    }

    foreach ($f in $payloadFiles) {
        try {
            $content = Get-Content $f.FullName -Raw | ConvertFrom-Json -ErrorAction Stop
            $id = $content.id
            if (-not $id) { Write-Host "Skipping $($f.Name): no id field"; continue }
            Write-Host "Backing up page $id"
            $resp = Invoke-RestMethod -Uri "$base/$id" -Headers $hdr -Method Get -ErrorAction Stop
            $resp | ConvertTo-Json -Depth 12 | Out-File -FilePath "outputs/backups/page_${id}_backup.json" -Encoding utf8
        }
        catch {
            Write-Warning "Failed to back up $($f.Name): $_"
        }
    }

    # TEST: POST page 4862
    $testId = 4862
    $testFile = Join-Path -Path "artifacts" -ChildPath "page_${testId}_update.json"
    if (Test-Path $testFile) {
        Write-Host "Posting test payload for page $testId..."
        $payload = Get-Content $testFile -Raw
        try {
            $result = Invoke-RestMethod -Uri "$base/$testId" -Headers $hdr -Method Post -Body $payload -ContentType 'application/json' -ErrorAction Stop
            $result | ConvertTo-Json -Depth 12 | Out-File -FilePath "outputs/post_results/page_${testId}_result.json" -Encoding utf8
            Write-Host "Test POST saved to outputs/post_results/page_${testId}_result.json"
            if ($result.title) { Write-Host "Returned title: $($result.title.rendered)" }
        }
        catch {
            Write-Error "Test POST failed: $_"; exit 1
        }
    }
    else {
        Write-Warning "Test payload $testFile not found; skipping test POST."
    }

    $go = Read-Host "Proceed with bulk POST for all pages? (Y/N)"
    if ($go.Trim().ToUpper() -ne 'Y') { Write-Host "Aborting bulk POSTs."; exit 0 }

    # Bulk POST
    foreach ($f in $payloadFiles) {
        try {
            $content = Get-Content $f.FullName -Raw | ConvertFrom-Json -ErrorAction Stop
            $id = $content.id
            if (-not $id) { Write-Warning "Skipping $($f.Name): missing id"; continue }
            Write-Host "Posting payload for page $id"
            $payload = Get-Content $f.FullName -Raw
            $resp = Invoke-RestMethod -Uri "$base/$id" -Headers $hdr -Method Post -Body $payload -ContentType 'application/json' -ErrorAction Stop
            $resp | ConvertTo-Json -Depth 12 | Out-File -FilePath "outputs/post_results/page_${id}_result.json" -Encoding utf8
        }
        catch {
            Write-Warning "Failed to POST $($f.Name): $_"
        }
    }

    # Header/navigation optional
    $doHeader = Read-Host "POST header and navigation payloads now? (Y/N)"
    if ($doHeader.Trim().ToUpper() -eq 'Y') {
        $headerFile = 'artifacts\header_payload.json'
        $navFile = 'artifacts\navigation_payload.json'
        if (Test-Path $headerFile) {
            try {
                Write-Host "Posting header payload..."
                $p = Get-Content $headerFile -Raw
                $r = Invoke-RestMethod -Uri 'https://loungenie.com/staging/wp-json/wp/v2/template-parts/header' -Headers $hdr -Method Post -Body $p -ContentType 'application/json' -ErrorAction Stop
                $r | ConvertTo-Json -Depth 12 | Out-File outputs/post_results/header_result.json -Encoding utf8
            }
            catch { Write-Warning "Header POST failed: $_" }
        }
        else { Write-Warning "Header payload not found: $headerFile" }

        if (Test-Path $navFile) {
            try {
                Write-Host "Posting navigation payload..."
                $p = Get-Content $navFile -Raw
                $r = Invoke-RestMethod -Uri 'https://loungenie.com/staging/wp-json/wp/v2/navigation' -Headers $hdr -Method Post -Body $p -ContentType 'application/json' -ErrorAction Stop
                $r | ConvertTo-Json -Depth 12 | Out-File outputs/post_results/navigation_result.json -Encoding utf8
            }
            catch { Write-Warning "Navigation POST failed: $_" }
        }
        else { Write-Warning "Navigation payload not found: $navFile" }
    }

    $runAudits = Read-Host "Run automated audits now (requires local Python/PowerShell scripts)? (Y/N)"
    if ($runAudits.Trim().ToUpper() -eq 'Y') {
        if (Test-Path .\scripts\run_automated_audits.ps1) {
            Write-Host "Running audits..."
            & .\scripts\run_automated_audits.ps1
            Write-Host "Audits finished; see artifacts/ for output."
        }
        else { Write-Warning "Audit runner not found at scripts/run_automated_audits.ps1" }
    }

    Write-Host "All done. Remember to rotate/revoke the Application Password after verification."
}
catch {
    Write-Error "Unexpected error: $_"
    exit 1
}
