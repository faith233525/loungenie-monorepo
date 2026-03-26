$ZipPath = $args[0]
$TargetDir = $args[1]

if (-not $ZipPath) { Write-Error 'Usage: pwsh upload_plugin_via_cpanel.ps1 <zipPath> <targetDir>'; exit 1 }
if (-not $TargetDir) { Write-Error 'Usage: pwsh upload_plugin_via_cpanel.ps1 <zipPath> <targetDir>'; exit 1 }

$host = $env:CPANEL_HOST
$user = $env:CPANEL_USER
$token = $env:CPANEL_TOKEN

if (-not $host -or -not $user -or -not $token) {
  Write-Error "Set CPANEL_HOST, CPANEL_USER and CPANEL_TOKEN environment variables before running."
  exit 1
}

$uri = "https://$host:2083/execute/Fileman/upload_files?dir=$([System.Uri]::EscapeDataString($TargetDir))"
Write-Output "Uploading $ZipPath to $uri"

$headers = @{ Authorization = "cpanel $user:$token" }

try {
  $fileContent = Get-Item -Path $ZipPath -ErrorAction Stop
  $form = @{ 'file-1' = Get-Item -Path $ZipPath }
  $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -InFile $ZipPath -ContentType 'multipart/form-data' -ErrorAction Stop
  Write-Output "cPanel response:"
  $resp | ConvertTo-Json -Depth 5 | Write-Output
} catch {
  Write-Error "Upload failed: $_"
  exit 1
}

Write-Output "Upload finished. If successful, use File Manager to extract the ZIP into $TargetDir or use WP Admin plugin upload."
