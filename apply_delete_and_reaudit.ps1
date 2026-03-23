$user='copilot'
$app='CumD bxvA KRVb C8u8 ygCg bzal'
$pair = "$user`:$app"
$b64=[Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers=@{Authorization="Basic $b64"}
$deleteBase='https://loungenie.com/Loungenie%E2%84%A2/index.php/wp-json/wp/v2/media'
$plan=Import-Csv media_duplicate_delete_plan.csv
$ok=0; $fail=0
$log=@()
foreach($r in $plan){
  $url="${deleteBase}/$($r.delete_id)?force=true"
  try{
    $resp = Invoke-RestMethod -Uri $url -Headers $headers -Method Delete -ErrorAction Stop
    if($resp.deleted -eq $true){
      $ok++
      $log += [PSCustomObject]@{id=$r.delete_id; status='deleted'; keep_id=$r.keep_id}
    } else {
      $fail++
      $log += [PSCustomObject]@{id=$r.delete_id; status='failed'; error='not_deleted'}
    }
  } catch {
    $fail++
    $log += [PSCustomObject]@{id=$r.delete_id; status='failed'; error=$_.Exception.Message}
  }
}
$log | Export-Csv -NoTypeInformation -Encoding UTF8 media_delete_results_force.csv

$base='https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/media'
$all=@()
for($p=1;$p -le 50;$p++){
  $u="${base}?per_page=100&page=$p&_fields=id,title,alt_text,file,media_details"
  try{
    $res=Invoke-RestMethod -Uri $u -Headers $headers -Method Get -ErrorAction Stop
    if($null -eq $res){break}
    if($res -isnot [System.Array]){ $res=@($res) }
    if($res.Count -eq 0){ break }
    $all += $res
    if($res.Count -lt 100){ break }
  } catch { break }
}
$rows = $all | ForEach-Object {
  $title = if($_.title -and $_.title.rendered){$_.title.rendered}else{''}
  [PSCustomObject]@{id=$_.id; title=$title; alt_text=$_.alt_text}
}
$missingAlt=($rows | Where-Object { [string]::IsNullOrWhiteSpace($_.alt_text) }).Count
$missingTitle=($rows | Where-Object { [string]::IsNullOrWhiteSpace($_.title) }).Count
"DELETED_OK=$ok;DELETED_FAIL=$fail;TOTAL_NOW=$($rows.Count);MISSING_ALT_NOW=$missingAlt;MISSING_TITLE_NOW=$missingTitle"
