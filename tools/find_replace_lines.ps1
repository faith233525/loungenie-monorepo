$lines = Get-Content .\run_wp_ftp_and_rest.ps1
for ($i=0; $i -lt $lines.Count; $i++) {
    if ($lines[$i] -match '-replace') {
        Write-Output ("{0}:{1}" -f ($i+1), $lines[$i])
    }
}
