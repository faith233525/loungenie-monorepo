[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$user = if($env:WP_USER){ $env:WP_USER } else { 'admin' }
$app  = if($env:WP_APP_PASSWORD){ $env:WP_APP_PASSWORD } else { 'i6IM cqLZ vQDC pIRk nKFr g35i' }
$pair = "$user`:$app"
$b64=[Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers=@{ Authorization="Basic $b64"; "Content-Type"="application/json" }
$base='https://www.loungenie.com/wp-json/wp/v2/media'
$plan=Import-Csv media_update_plan.csv
$ok=0; $fail=0
$log=@()
foreach($r in $plan){
  $body=@{}
  if(-not [string]::IsNullOrWhiteSpace($r.new_alt)){ $body.alt_text = $r.new_alt }
  if(-not [string]::IsNullOrWhiteSpace($r.new_title)){ $body.title = $r.new_title }
  if($body.Keys.Count -eq 0){ continue }
  $url="${base}/$($r.id)"
  try{
    $jsonBody = $body | ConvertTo-Json -Compress
    Invoke-RestMethod -Uri $url -Headers $headers -Method Post -Body $jsonBody -ErrorAction Stop | Out-Null
    $ok++
    $log += [PSCustomObject]@{id=$r.id; status='updated'}
  } catch {
    $fail++
    $log += [PSCustomObject]@{id=$r.id; status='failed'; error=$_.Exception.Message}
  }
}
$log | Export-Csv -NoTypeInformation -Encoding UTF8 media_update_results.csv
"UPDATED_OK=$ok;UPDATED_FAIL=$fail"
