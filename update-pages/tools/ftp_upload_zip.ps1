Param(
    [Parameter(Mandatory=$true)][string]$LocalZip,
    [Parameter(Mandatory=$true)][string]$FtpHost,
    [Parameter(Mandatory=$true)][string]$FtpUser,
    [Parameter(Mandatory=$true)][string]$FtpPass,
    [string]$RemotePath = "/public_html/wp-content/uploads/",
    [switch]$Overwrite
)

if (-not (Test-Path $LocalZip)) {
    Write-Error "Local zip not found: $LocalZip"
    exit 1
}

$uri = "ftp://$FtpHost$RemotePath$(Split-Path $LocalZip -Leaf)"
Write-Output "Uploading $LocalZip -> $uri"

$wc = New-Object System.Net.WebClient
$wc.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
try {
    # Use UploadFile for simpler, reliable FTP transfer
    $wc.UploadFile($uri, $LocalZip)
    Write-Output "Upload complete"
} catch {
    Write-Error "FTP upload failed: $($_.Exception.Message)"
    exit 1
}
