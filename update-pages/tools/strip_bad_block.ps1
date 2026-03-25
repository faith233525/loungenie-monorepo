$path = Join-Path $PSScriptRoot '..\run_wp_ftp_and_rest.ps1'
$lines = [System.IO.File]::ReadAllLines($path)
$idx = -1
for ($i=0; $i -lt $lines.Length; $i++) {
    if ($lines[$i] -match "\$name = \$name -replace ' {2,}',''" -or $lines[$i] -match "\$name = \$name -replace '    ',''") {
        $idx = $i; break
    }
}
if ($idx -ge 0) {
    $removeCount = 20
    $before = $lines[0..($idx-1)]
    $afterStart = $idx + $removeCount
    if ($afterStart -lt $lines.Length) { $after = $lines[$afterStart..($lines.Length-1)] } else { $after = @() }
    $replacement = @(
        '        # Normalize whitespace and remove control characters',
        "        $name = $name -replace '\\s+', ' '",
        "        $name = $name -replace '[\\r\\n]+', ''",
        '        $name = $name.Trim()',
        '',
        '        # Build remote item path and normalize path segments',
        '        $remoteItem = "' + "$remotePath/$name" + '" -replace ''/{2,}'', ''/'')',
        '        while ($remoteItem -match ''/\./'') { $remoteItem = $remoteItem -replace ''/\./'', ''/'' }',
        '        while ($remoteItem -match ''/[^/]+/\.\.'') { $remoteItem = $remoteItem -replace ''/[^/]+/\.\.'', '''' }'
    )
    $new = $before + $replacement + $after
    [System.IO.File]::WriteAllLines($path, $new)
    Write-Output "Stripped bad block at index $idx and inserted normalized block."
} else {
    Write-Output "No bad block found."
}
