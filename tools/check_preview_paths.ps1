$paths = @('https://loungenie.com/loungenie-home-preview.php','https://loungenie.com/staging/loungenie-home-preview.php')
foreach ($p in $paths) {
    try {
        $r = Invoke-WebRequest $p -UseBasicParsing -ErrorAction Stop
        $out = Split-Path -Leaf $p
        $r.Content | Out-File (".\exports\preview_" + $out) -Encoding utf8
        Write-Host "Saved: $p -> exports\preview_$out"
    } catch {
        Write-Host "Not found: $p"
    }
}
