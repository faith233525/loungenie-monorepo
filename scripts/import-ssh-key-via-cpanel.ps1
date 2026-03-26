param(
    [string]$KeyFile = "id_copilot.pub",
    [string]$CpUser = 'pools425',
    [string]$CpToken = 'V0MGL6D2UCTKRSPMKCDU1VU2FY3295QC'
)

if(-not (Test-Path $KeyFile)){
    Write-Error "Public key file not found: $KeyFile"
    exit 1
}

$pub = Get-Content -Raw -Path $KeyFile
$headers = @{ Authorization = "cpanel $($CpUser):$($CpToken)" }
$body = @{ name = 'copilot_key'; key = $pub }

# Ensure modern TLS
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$uri = 'https://poolsafeinc.com:2083/execute/SSH/import_authorized_key'
$maxAttempts = 3
$attempt = 0
$success = $false

while (-not $success -and $attempt -lt $maxAttempts) {
    $attempt++
    try {
        $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body -ContentType 'application/x-www-form-urlencoded' -TimeoutSec 120 -ErrorAction Stop
        $resp | ConvertTo-Json -Depth 6 | Out-File -FilePath .\cpanel_import.json -Encoding UTF8
        Write-Host "IMPORT_OK - Attempt $attempt"
        Get-Content .\cpanel_import.json -Raw | Write-Host
        $success = $true
        break
    } catch {
        $err = $_.Exception.Message
        $line = "{0} IMPORT_ERR Attempt {1}: {2}" -f (Get-Date).ToString('s'), $attempt, $err
        $line | Out-File -FilePath .\cpanel_import_error.log -Append -Encoding UTF8
        Start-Sleep -Seconds (5 * $attempt)
    }
}

if (-not $success) {
    Write-Host "IMPORT_FAILED after $maxAttempts attempts. See cpanel_import_error.log for details."
    exit 2
}
