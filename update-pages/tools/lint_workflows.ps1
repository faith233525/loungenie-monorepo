$workflows = Get-ChildItem -Path .github/workflows -Filter '*.yml'
foreach ($wf in $workflows) {
    Write-Output "--- $($wf.FullName)"
    try {
        $text = Get-Content -Path $wf.FullName -Raw
        # ConvertFrom-Yaml available in PowerShell 7+
        $obj = $text | ConvertFrom-Yaml
        Write-Output 'OK'
    } catch {
        Write-Output "ERROR: $($wf.FullName) -> $($_.Exception.Message)"
    }
}
