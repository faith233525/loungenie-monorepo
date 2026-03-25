Param(
    [Parameter(Mandatory=$true)][string]$LocalFile,
    [Parameter(Mandatory=$true)][string]$FtpHost,
    [Parameter(Mandatory=$true)][string]$FtpUser,
    [Parameter(Mandatory=$true)][string]$FtpPass,
    [string]$RemotePath = "/public_html/wp-content/uploads/",
    [switch]$Overwrite
)

if (-not (Test-Path $LocalFile)) {
    Write-Error "Local file not found: $LocalFile"
    exit 1
}

$remoteFileName = [System.IO.Path]::GetFileName($LocalFile)
$uri = "ftp://$FtpHost$RemotePath$remoteFileName"
Write-Output "Uploading $LocalFile -> $uri"

$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
try {
    $wc.UploadFile($uri, $LocalFile)
    Write-Output "Upload complete"
} catch {
    Write-Error "FTP upload failed: $($_.Exception.Message)"
    exit 1
}
