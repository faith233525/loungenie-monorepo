param()

$hosts = @(
    'https://www.loungenie.com/staging_loungenie/wp-oneclick-install-2.php',
    'https://www.loungenie.com/staging/wp-oneclick-install-2.php',
    'https://www.loungenie.com/staging_loungenie/wp-oneclick-install-retry.php',
    'https://www.loungenie.com/staging/wp-oneclick-install-retry.php',
    'https://www.loungenie.com/staging_loungenie/wp-admin/install.php',
    'https://www.loungenie.com/staging/wp-admin/install.php',
    'http://www.loungenie.com/staging_loungenie/wp-oneclick-install-2.php',
    'http://loungenie.com/staging_loungenie/wp-oneclick-install-2.php',
    'https://www.loungenie.com/staging_loungenie/wp-oneclick-install.php',
    'http://www.loungenie.com/staging_loungenie/wp-oneclick-install.php'
)

$outDir = Join-Path $PWD 'probe-results'
if(-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

foreach($u in $hosts){
    $fname = [IO.Path]::Combine($outDir, ([System.Uri]$u).Host + '_' + ([System.Uri]$u).AbsolutePath.Replace('/','_').TrimStart('_') + '.txt')
    Write-Host '---'
    Write-Host $u
    $cmd = "curl.exe -s -k -H 'Host: www.loungenie.com' -H 'Cache-Control: no-cache' -I '$u' -w '\nHTTP_CODE:%{http_code}'"
    Write-Host $cmd
    try{
        $res = Invoke-Expression $cmd 2>&1
        $res | Out-File -FilePath $fname -Encoding UTF8
        Write-Host "Saved -> $fname"
        $head = Get-Content $fname -TotalCount 20
        $head | ForEach-Object { Write-Host $_ }
    } catch {
        Write-Host "ERR for $u -> $_"
    }
}

Write-Host 'Probe complete. Results in:'
Write-Host $outDir
