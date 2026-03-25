$user='copilot'
$app='CumD bxvA KRVb C8u8 ygCg bzal'
$pair = "$user`:$app"
$b64=[Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$headers=@{Authorization="Basic $b64"}
$base='https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/media'
$all=@()
for($p=1;$p -le 50;$p++){
  $url="${base}?per_page=100&page=$p&_fields=id,slug,title,alt_text,source_url,mime_type,media_details"
  try{
    $res=Invoke-RestMethod -Uri $url -Headers $headers -Method Get -ErrorAction Stop
    if($null -eq $res){break}
    if($res -isnot [System.Array]){ $res=@($res) }
    if($res.Count -eq 0){break}
    $all += $res
    if($res.Count -lt 100){break}
  } catch {
    Write-Output "PAGE_ERROR=$p;$($_.Exception.Message)"
    break
  }
}
$rows = $all | ForEach-Object {
  $title = if($_.title -and $_.title.rendered){$_.title.rendered}else{''}
  $file = if($_.media_details -and $_.media_details.file){$_.media_details.file}else{''}
  $filesize = if($_.media_details -and $_.media_details.filesize){$_.media_details.filesize}else{0}
  $w = if($_.media_details -and $_.media_details.width){$_.media_details.width}else{0}
  $h = if($_.media_details -and $_.media_details.height){$_.media_details.height}else{0}
  [PSCustomObject]@{ id=$_.id; slug=$_.slug; title=$title; alt_text=$_.alt_text; source_url=$_.source_url; mime_type=$_.mime_type; file=$file; filesize=$filesize; width=$w; height=$h }
}
$rows | Export-Csv -NoTypeInformation -Encoding UTF8 media_audit.csv
$dupsFile = $rows | Group-Object file | Where-Object { $_.Name -and $_.Count -gt 1 } | ForEach-Object {
  [PSCustomObject]@{key=$_.Name; count=$_.Count; ids=($_.Group.id -join ',')}
}
$dupsShape = $rows | Group-Object mime_type,filesize,width,height | Where-Object { $_.Count -gt 1 } | ForEach-Object {
  [PSCustomObject]@{key=$_.Name; count=$_.Count; ids=($_.Group.id -join ','); files=($_.Group.file -join ',')}
}
$dupsFile | Export-Csv -NoTypeInformation -Encoding UTF8 media_duplicates_file.csv
$dupsShape | Export-Csv -NoTypeInformation -Encoding UTF8 media_duplicates_shape.csv
$missingAlt=($rows | Where-Object { [string]::IsNullOrWhiteSpace($_.alt_text) }).Count
$missingTitle=($rows | Where-Object { [string]::IsNullOrWhiteSpace($_.title) }).Count
"TOTAL=$($rows.Count);MISSING_ALT=$missingAlt;MISSING_TITLE=$missingTitle;DUP_FILE_GROUPS=$($dupsFile.Count);DUP_SHAPE_GROUPS=$($dupsShape.Count)"
