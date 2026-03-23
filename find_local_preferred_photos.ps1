$root = "C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos"
Get-ChildItem -Path $root -Recurse -File -Include *.jpg, *.jpeg, *.png, *.webp |
Where-Object { $_.Name -match 'balmoral|six.?flags|grove|westin|wild.?rivers|cowabunga|water.?world|marriott|hilton' } |
Select-Object -First 300 -ExpandProperty FullName
