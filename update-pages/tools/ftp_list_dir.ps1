param(
    [string[]]$Paths = @('/','/wp-content','/wp-content/mu-plugins','/public_html','/public_html/wp-content','/staging','/staging/wp-content')
)
. .\tools\session_workspace.ps1
foreach ($p in $Paths) {
    try {
        Write-Host "Listing FTP path: $p"
        $uri = "ftp://$($env:FTP_HOST)$p"
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
        $req.Credentials = New-Object System.Net.NetworkCredential($env:FTP_USER,$env:FTP_PASS)
        $resp = $req.GetResponse()
        $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
        $content = $sr.ReadToEnd()
        $sr.Close(); $resp.Close();
        if ($content) { Write-Host $content } else { Write-Host '(empty)' }
    } catch {
        Write-Host "Failed to list $p : $($_.Exception.Message)"
    }
}
