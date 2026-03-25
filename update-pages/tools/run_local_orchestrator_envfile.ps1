<#
One-shot runner that reads credentials from `tools/.env.local` (local only),
sets environment variables for this session, and calls `run_local_orchestrator.ps1`.

Create `tools/.env.local` with the exact keys shown in the sample file.
Do NOT commit the file. `tools/.gitignore` will ignore it.
#>

param(
    [string]$EnvFile = "$PSScriptRoot\.env.local"
)

if (!(Test-Path $EnvFile)) {
    Write-Host "Env file not found: $EnvFile" -ForegroundColor Yellow
    Write-Host "Create $EnvFile with the following keys (no surrounding quotes):"
    Write-Host "FTP_HOST=ftp.loungenie.com"
    Write-Host "FTP_USER=copilot@loungenie.com"
    Write-Host "FTP_PASS=your_ftp_password_here"
    Write-Host "WP_USER=copilot"
    Write-Host "WP_PASS=your_wp_application_password_here"
    exit 2
}

$lines = Get-Content $EnvFile | ForEach-Object { $_.Trim() } | Where-Object { $_ -and -not $_.StartsWith('#') }
foreach ($line in $lines) {
    if ($line -match '^(?:export\s+)?([^=\s]+)\s*=\s*(.*)$') {
        $k = $matches[1]
        $v = $matches[2]
        # Remove surrounding quotes if present
        if ($v.StartsWith('"') -and $v.EndsWith('"')) { $v = $v.Substring(1, $v.Length-2) }
        if ($v.StartsWith("'") -and $v.EndsWith("'")) { $v = $v.Substring(1, $v.Length-2) }
        Set-Item -Path env:$k -Value $v
    }
}

Write-Host "Environment variables loaded from $EnvFile. Running orchestrator..."
& "$PSScriptRoot\run_local_orchestrator.ps1"
Write-Host "Orchestrator run finished." -ForegroundColor Green
