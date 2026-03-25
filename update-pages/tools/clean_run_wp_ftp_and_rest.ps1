$path = Join-Path $PSScriptRoot '..\run_wp_ftp_and_rest.ps1'
$lines = [System.IO.File]::ReadAllLines($path)
$out = New-Object System.Collections.Generic.List[string]
$inserted = $false
for ($i=0; $i -lt $lines.Length; $i++) {
    $line = $lines[$i]
    if ($line -match "^\s*\$name = \$name -replace\s*'\s*',''" -or $line -match "^\s*\$name = \$name -replace\s*'\\r'" -or $line -match "^\s*\$name = \$name -replace\s*'\\n'" -or $line -match "^\s*\$name = \$name -replace\s*'\\r\\n'" -or $line -match "^\s*\$name = \$name -replace\s*'' ,''") {
        if (-not $inserted) {
            $out.Add("        # Normalize whitespace and remove control characters")
            $out.Add("        $name = $name -replace '\\s+', ' '\")
            $out.Add("        $name = $name -replace '[\\r\\n]+', ''")
            $out.Add("        $name = $name.Trim()")
            $out.Add("")
            $out.Add("        # Build remote item path and normalize path segments")
            $out.Add('        $remoteItem = "' + "$remotePath/$name" + '" -replace ''/{2,}'', ''/'')')
            $out.Add('        while ($remoteItem -match ''/\./'') { $remoteItem = $remoteItem -replace ''/\./'', ''/'' }')
            $out.Add('        while ($remoteItem -match ''/[^/]+/\.\.'') { $remoteItem = $remoteItem -replace ''/[^/]+/\.\.'', '''' }')
            $inserted = $true
        }
        # skip this line (broken)
        continue
    }
    else {
        $out.Add($line)
    }
}
[System.IO.File]::WriteAllLines($path, $out.ToArray())
Write-Output "Cleaned run_wp_ftp_and_rest.ps1; removed broken replace lines and inserted normalized block."