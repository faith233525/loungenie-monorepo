function Get-InputRows {
  $candidates = @('media_audit.csv', 'wp_media.csv') | Where-Object { Test-Path $_ }
  if($candidates.Count -eq 0){
    throw 'No input CSV found. Run export_wp_content_and_media.ps1 or refresh_media_audit.py first.'
  }

  $bestFile = $null
  $bestTime = [datetime]::MinValue
  foreach($f in $candidates){
    $mtime = (Get-Item $f).LastWriteTimeUtc
    if($mtime -gt $bestTime){
      $bestTime = $mtime
      $bestFile = $f
    }
  }

  $src = Import-Csv $bestFile
  if(-not $src -or $src.Count -eq 0){
    throw "Input file '$bestFile' is empty."
  }

  Write-Host "Using input file: $bestFile"

  $normalized = foreach($r in $src){
    $sourceUrl = [string]$r.source_url
    $file = [string]$r.file
    if([string]::IsNullOrWhiteSpace($file) -and -not [string]::IsNullOrWhiteSpace($sourceUrl)){
      try{
        $u = [Uri]$sourceUrl
        $file = $u.AbsolutePath.TrimStart('/')
        if($file -like 'wp-content/uploads/*'){
          $file = $file.Substring('wp-content/uploads/'.Length)
        }
      } catch {
        $file = ''
      }
    }

    [PSCustomObject]@{
      id       = [int]$r.id
      slug     = [string]$r.slug
      title    = [string]$r.title
      alt_text = [string]$r.alt_text
      source_url = $sourceUrl
      file     = $file
      filesize = [int]($r.filesize)
      width    = [int]($r.width)
      height   = [int]($r.height)
    }
  }

  return $normalized
}

$rows = Get-InputRows
function Clean-Label([string]$s){
  if([string]::IsNullOrWhiteSpace($s)){ return '' }
  $x = [System.IO.Path]::GetFileNameWithoutExtension($s)
  $x = $x -replace '[_-]+',' '
  $x = $x -replace '\s+',' '
  return $x.Trim()
}
$plan=@()
foreach($r in $rows){
  $title=[string]$r.title
  $alt=[string]$r.alt_text
  $slug=[string]$r.slug
  $file=[string]$r.file
  $base= if(-not [string]::IsNullOrWhiteSpace($title)){ $title } elseif(-not [string]::IsNullOrWhiteSpace($slug)){ $slug } else { $file }
  $label=Clean-Label $base
  if($label -match '^(IMG|DSC|PXL|PHOTO|Screenshot)\s*\d+' -or $label -match '^IMG\s*\d+$'){
    $label='LounGenie poolside hospitality photo'
  }
  if([string]::IsNullOrWhiteSpace($label)){ $label='LounGenie image' }

  $newAlt=$null
  $newTitle=$null
  if([string]::IsNullOrWhiteSpace($alt)){ $newAlt=$label }
  if([string]::IsNullOrWhiteSpace($title)){ $newTitle=$label }

  if($newAlt -or $newTitle){
    $plan += [PSCustomObject]@{id=[int]$r.id; file=$file; old_title=$title; old_alt=$alt; new_title=$newTitle; new_alt=$newAlt}
  }
}
$plan | Export-Csv -NoTypeInformation -Encoding UTF8 media_update_plan.csv

$dupPlan=@()
$bySignature = $rows | Group-Object {
  $f = [string]$_.file
  if([string]::IsNullOrWhiteSpace($f)){ return '' }
  "$($_.filesize)|$($_.width)|$($_.height)|$([System.IO.Path]::GetFileNameWithoutExtension($f) -replace '-\d+$','')"
} | Where-Object { $_.Name -ne '' }
foreach($g in $bySignature){
  if($g.Count -lt 2){ continue }
  $group=$g.Group
  $withSuffix=$group | Where-Object { $_.file -match '-\d+\.[A-Za-z0-9]+$' }
  $withoutSuffix=$group | Where-Object { $_.file -notmatch '-\d+\.[A-Za-z0-9]+$' }
  foreach($d in $withSuffix){
    if($withoutSuffix.Count -gt 0){
      $dupPlan += [PSCustomObject]@{delete_id=[int]$d.id; delete_file=$d.file; keep_id=[int]$withoutSuffix[0].id; keep_file=$withoutSuffix[0].file}
    }
  }
}
$dupPlan = $dupPlan | Sort-Object delete_id -Unique
$dupPlan | Export-Csv -NoTypeInformation -Encoding UTF8 media_duplicate_delete_plan.csv
"METADATA_UPDATES=$($plan.Count);DUPLICATES_TO_DELETE=$($dupPlan.Count)"
