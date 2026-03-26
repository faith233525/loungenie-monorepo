# uapi-capture.ps1
# Calls cPanel UAPI endpoints and saves raw responses to workspace files for diagnosis.

$cpHost = 'poolsafeinc.com'
$cpUser = 'pools425'
$cpToken = 'V0MGL6D2UCTKRSPMKCDU1VU2FY3295QC'
$base = 'https://' + $cpHost + ':2083/execute'
$headers = @{ Authorization = "cpanel ${cpUser}:${cpToken}"; 'Content-Type' = 'application/json'; Accept = 'application/json' }

function CallUapiGet([string]$endpoint, [string]$outfile) {
    $url = "$base/$endpoint"
    Write-Host "GET $url -> $outfile"
    try {
        $resp = Invoke-WebRequest -Uri $url -Headers $headers -Method Get -SkipCertificateCheck -UseBasicParsing
        $content = $resp.Content
        $status = $resp.StatusCode
    } catch {
        $content = $_.Exception.Message
        $status = 'ERROR'
    }
    Set-Content -Path $outfile -Value $content -Force
    Write-Host "Wrote $outfile (status: $status)"
}

function CallUapiPost([string]$endpoint, [object]$bodyObj, [string]$outfile) {
    $url = "$base/$endpoint"
    $bodyJson = $bodyObj | ConvertTo-Json -Depth 10
    Write-Host "POST $url -> $outfile"
    try {
        $resp = Invoke-WebRequest -Uri $url -Headers $headers -Body $bodyJson -Method Post -SkipCertificateCheck -UseBasicParsing
        $content = $resp.Content
        $status = $resp.StatusCode
    } catch {
        $content = $_.Exception.Message
        $status = 'ERROR'
    }
    Set-Content -Path $outfile -Value $content -Force
    Write-Host "Wrote $outfile (status: $status)"
}

# 1) list_domains
CallUapiGet 'DomainInfo/list_domains' '.\uapi-list_domains.json'
# 2) create_active_domain (main)
$body = @{ domain = 'loungenie.com'; dir = '/public_html/main' }
CallUapiPost 'DomainInfo/create_active_domain' $body '.\uapi-create_active_domain.json'
# 3) add subdomain support
$body = @{ domain = 'support'; rootdomain = 'loungenie.com'; dir = '/public_html/support' }
CallUapiPost 'SubDomain/addsubdomain' $body '.\uapi-addsubdomain.json'
# 4) create mysql database
$ts = (Get-Date).ToString('yyMMddHHmm')
# MySQL databases/users must be prefixed with the cPanel account name (e.g. pools425_)
$dbName = "${cpUser}_lounge_$ts"
CallUapiGet ("Mysql/create_database?name=$dbName") ".\uapi-mysql-create_database.json"
# 5) create mysql user
# MySQL user also needs the cPanel account prefix
$dbUser = "${cpUser}_loungeu_$ts"
# generate password
Add-Type -AssemblyName System.Security
$bytes = New-Object 'System.Byte[]' 16
[System.Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($bytes)
$dbPass = [System.Convert]::ToBase64String($bytes) -replace '[+/=]', ''
$encPass = [System.Uri]::EscapeDataString($dbPass)
CallUapiGet ("Mysql/create_user?name=$dbUser&password=$encPass") ".\uapi-mysql-create_user.json"
# 6) set privileges
CallUapiGet ("Mysql/set_privileges_on_database?user=$dbUser&database=$dbName&privileges=ALL%20PRIVILEGES") ".\uapi-mysql-set_privs.json"
# 7) trigger AutoSSL
CallUapiGet 'SSL/autossl_check?domain=loungenie.com' '.\uapi-autossl_check.json'

Write-Host "All calls complete. Files written:"
Get-ChildItem -Path . -Filter 'uapi-*.json' | ForEach-Object { Write-Host $_.Name }

# Print concise summaries
foreach ($f in Get-ChildItem -Path . -Filter 'uapi-*.json') {
    Write-Host "--- $($f.Name) ---"
    $content = Get-Content $f.FullName -Raw
    if ($content.Length -gt 4000) { Write-Host $content.Substring(0,4000); Write-Host "... (truncated)" } else { Write-Host $content }
}

# At end, store DB creds to a file for your use (but not printed)
$cred = @{ database = $dbName; db_user = $dbUser; db_pass = $dbPass }
$cred | ConvertTo-Json | Set-Content -Path '.\staging-db-credentials.json' -Force
Write-Host "Wrote staging-db-credentials.json (contains DB creds)."
