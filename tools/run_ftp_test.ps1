# Create a small test directory and upload it via FTP using session helper
$testDir = Join-Path (Get-Location) 'build\ftp-test'
if (-not (Test-Path $testDir)) { New-Item -Path $testDir -ItemType Directory | Out-Null }
$testFile = Join-Path $testDir 'ping.txt'
Set-Content -Path $testFile -Value "ftp test: $(Get-Date -Format o)" -Encoding UTF8

. .\tools\session_workspace.ps1

Upload-PluginDir -LocalDir $testDir -RemotePath '/wp-content/uploads/loungenie-test'

Write-Host "FTP test upload attempted for $testFile" -ForegroundColor Green
