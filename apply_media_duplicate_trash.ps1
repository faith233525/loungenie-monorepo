[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$user = if($env:WP_USER){ $env:WP_USER } else { 'admin' }
$app  = if($env:WP_APP_PASSWORD){ $env:WP_APP_PASSWORD } else { 'i6IM cqLZ vQDC pIRk nKFr g35i' }
$pair = "$user`:$app"
$b64=[Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers=@{ Authorization="Basic $b64"; "Content-Type"="application/json" }
$base='https://www.loungenie.com/wp-json/wp/v2/media'
$plan=Import-Csv media_duplicate_delete_plan.csv
$ok=0; $fail=0
$log=@()
foreach($r in $plan){
  $url="${base}/$($r.delete_id)"
  try{
    Invoke-RestMethod -Uri $url -Headers $headers -Method Delete -Body (@{ force='true' } | ConvertTo-Json -Compress) -ErrorAction Stop | Out-Null
    $ok++
    $log += [PSCustomObject]@{id=$r.delete_id; status='trashed'; keep_id=$r.keep_id}
  } catch {
    $fail++
    $err = $_ | Out-String
    $log += [PSCustomObject]@{id=$r.delete_id; status='failed'; error=$err}
    Write-Host "Delete failed for $($r.delete_id): $err" -ForegroundColor Red
  }
}
$log | Export-Csv -NoTypeInformation -Encoding UTF8 media_delete_results.csv
"TRASHED_OK=$ok;TRASHED_FAIL=$fail"
