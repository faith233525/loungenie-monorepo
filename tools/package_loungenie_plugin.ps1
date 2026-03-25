Param(
    [string]$PluginDir = "tools/loungenie-block-patterns",
    [string]$OutZip = "tools/loungenie-block-patterns.zip",
    [switch]$Overwrite
)

if (-Not (Test-Path $PluginDir)) {
    Write-Error "Plugin directory '$PluginDir' not found."
    exit 1
}

if (Test-Path $OutZip) {
    if ($Overwrite.IsPresent) { Remove-Item $OutZip -Force }
    else { Write-Output "Zip already exists at $OutZip. Use -Overwrite to recreate."; exit 0 }
}

Write-Output "Creating ZIP of $PluginDir -> $OutZip"
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($PluginDir, $OutZip)
Write-Output "Created $OutZip"
