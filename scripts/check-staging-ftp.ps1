$ftpHost = $env:FTP_HOST
$ftpUser = $env:FTP_USER
$ftpPass = $env:FTP_PASS
if (-not ($ftpHost -and $ftpUser -and $ftpPass)) { Write-Error 'FTP env vars not set'; exit 1 }

function Test-FtpFile {
    param([string]$path)
    $uri = "ftp://$ftpHost/$path"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::GetFileSize
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $resp = $req.GetResponse()
        Write-Host "FOUND: $path (size=$($resp.ContentLength))"
        $resp.Close()
    } catch {
        Write-Host "MISSING: $path - $($_.Exception.Message)"
    }
}

function Test-FtpDir {
    param([string]$dir)
    $uri = "ftp://$ftpHost/$dir"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $resp = $req.GetResponse()
        $sr = New-Object IO.StreamReader $resp.GetResponseStream()
        $txt = $sr.ReadToEnd()
        $sr.Close()
        $resp.Close()
        Write-Host "DIR LIST: $dir`n$txt"
    } catch {
        Write-Host "DIR MISSING/ERR: $dir - $($_.Exception.Message)"
    }
}

Test-FtpFile 'public_html/staging_loungenie/wp-config.php'
Test-FtpFile 'public_html/staging_loungenie/wp-login.php'
Test-FtpDir 'public_html/staging_loungenie/wp-admin'
