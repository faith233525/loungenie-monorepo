<#
.SYNOPSIS
  Generate a simple server-rendered Gutenberg block plugin from an HTML file.

USAGE
  .\generate_html_block_plugin.ps1 -HtmlPath .\tools\page_4701_home.html -Slug lougie-home -Title "Loungenie Home"

DESCRIPTION
  Creates a plugin folder under build/plugins/<slug> containing:
  - <slug>.php    (plugin bootstrap which registers a server-rendered block)
  - block.html    (the HTML content to render)

  The plugin uses a PHP render callback to output the raw HTML (no build step required).
#>

param(
    [Parameter(Mandatory=$true)] [string]$HtmlPath,
    [Parameter(Mandatory=$true)] [string]$Slug,
    [Parameter(Mandatory=$true)] [string]$Title
)

if (-not (Test-Path $HtmlPath)) {
    Write-Error "Html file not found: $HtmlPath"
    exit 1
}

$outDir = Join-Path (Get-Location) "build\plugins\$Slug"
if (-not (Test-Path $outDir)) { New-Item -Path $outDir -ItemType Directory -Force | Out-Null }

$blockHtml = Join-Path $outDir 'block.html'
Copy-Item -Path $HtmlPath -Destination $blockHtml -Force

$phpFile = Join-Path $outDir "$Slug.php"
$phpContent = @"
<?php
/*
Plugin Name: $Title
Description: Auto-generated plugin that registers a server-rendered block outputting provided HTML.
Version: 0.1
Author: Copilot
*/

function {$Slug}_render_block( $attributes ) {
    $file = plugin_dir_path( __FILE__ ) . 'block.html';
    if ( file_exists( $file ) ) {
        return file_get_contents( $file );
    }
    return '';
}

function {$Slug}_register_block() {
    register_block_type( 'custom/{$Slug}', array( 'render_callback' => '{$Slug}_render_block' ) );
}
add_action( 'init', '{$Slug}_register_block' );

?>
"@

Set-Content -Path $phpFile -Value $phpContent -Encoding UTF8

Write-Host "Generated plugin at: $outDir" -ForegroundColor Green
Write-Host "Install/upload this folder into wp-content/plugins and activate the plugin to make the block available in the editor." -ForegroundColor Yellow
