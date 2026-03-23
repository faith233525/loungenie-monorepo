$json = Get-Content -Raw -Path 'page_4701_backup.json'
$m = [regex]::Match($json, '<style>.*?</style>', 'Singleline')
if ($m.Success) {
    $style = $m.Value -replace '(^<style>\s*)|(\s*</style>$)', ''
    Add-Content -Path 'wp-content/plugins/lg-block-patterns/assets/css/style.css' -Value "`n/* Extracted inline CSS from page_id=4701 */`n$style`n"
    $html = $json.Substring($m.Index + $m.Length)
    $wrapped = @'
<!-- wp:html -->
'@ + $html + @'
<!-- /wp:html -->
'@
    Set-Content -Path 'page_4701_no_style.html' -Value $wrapped -Encoding UTF8
    & curl -s -u 'copilot:3Fme PIDq hS4a 2oRe 4z19 4Eyp' -X POST 'https://loungenie.com/staging/wp-json/wp/v2/pages/4701' -F 'content=@page_4701_no_style.html' -o page_4701_update.json
    Write-Output 'page 4701 processed'
}
else {
    Write-Output 'no <style> found for 4701'
}
