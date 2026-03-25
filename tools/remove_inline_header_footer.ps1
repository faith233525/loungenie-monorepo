# Remove inline header and footer blocks from pages, leaving the page body (hero/carousel/bento)
param(
    [switch]$DryRun
)

$TargetIds = @(4701,4862,2989,5223,5285,5139)
foreach ($id in $TargetIds) {
    try {
        $u = "https://loungenie.com/staging/wp-json/wp/v2/pages/$id"
        $page = Invoke-RestMethod -Uri $u -Method Get -ErrorAction Stop
        $html = $page.content.rendered

        # Remove the inline global header block (from the header comment to the Hero comment)
        $html2 = [regex]::Replace($html, '(?s)<!-- Global header block \(inline safe header\) -->.*?<!-- Hero:', '<!-- Hero:', 'IgnoreCase')

        # Remove the inline master footer block (footer comment through its closing container)
        $html2 = [regex]::Replace($html2, '(?s)<!-- Footer: 4-column corporate footer with TSXV ticker -->.*?</div></div>\s*', '', 'IgnoreCase')

        if ($html2 -eq $html) {
            Write-Output "No inline header/footer found for $id; skipping."
            continue
        }

        if ($DryRun) {
            Write-Output "DryRun: would update page $id — preview snippet:";
            $snippet = $html2.Substring(0, [Math]::Min(1000, $html2.Length))
            Write-Output $snippet
            continue
        }

        # Apply change
        $payload = @{ content = $html2; status = $page.status } | ConvertTo-Json -Depth 12
        $auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
        $resp = Invoke-RestMethod -Uri $u -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $payload -ErrorAction Stop
        Write-Output "Updated $id -> status: $($resp.status) id: $($resp.id)"
    } catch {
        Write-Warning ("Failed {0}: {1}" -f $id, $_.Exception.Message)
    }
}
