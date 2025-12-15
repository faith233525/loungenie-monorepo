#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Build clean production deployment package for PoolSafe Portal
.DESCRIPTION
    Creates a production-ready ZIP file with only necessary files
    - Excludes: node_modules, tests, docs, dev files
    - Includes: Core plugin files, assets, templates
    - Validates: PHP syntax before packaging
.EXAMPLE
    .\build-production.ps1
#>

param(
    [string]$OutputDir = ".",
    [string]$Version = "3.2.5"
)

$ErrorActionPreference = "Stop"

Write-Host "`n" -NoNewline
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  POOLSAFE PORTAL - PRODUCTION BUILD SCRIPT v$Version" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Define package name
$packageName = "wp-poolsafe-portal-v$Version-production"
$packageDir = Join-Path $OutputDir $packageName
$zipFile = Join-Path $OutputDir "$packageName.zip"

# Clean previous build
if (Test-Path $packageDir) {
    Write-Host "Removing previous build directory..." -ForegroundColor Yellow
    Remove-Item -Path $packageDir -Recurse -Force
}
if (Test-Path $zipFile) {
    Write-Host "Removing previous ZIP file..." -ForegroundColor Yellow
    Remove-Item -Path $zipFile -Force
}

# Create package directory
Write-Host "Creating package directory..." -ForegroundColor Cyan
New-Item -ItemType Directory -Path $packageDir -Force | Out-Null
$pluginDir = Join-Path $packageDir "wp-poolsafe-portal"
New-Item -ItemType Directory -Path $pluginDir -Force | Out-Null

Write-Host "Copying production files..." -ForegroundColor Cyan

# Core plugin files
$coreFiles = @(
    "wp-poolsafe-portal.php",
    "uninstall.php",
    "readme.txt",
    "README.md",
    ".htaccess"
)

foreach ($file in $coreFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $pluginDir -Force
        Write-Host "  OK: $file" -ForegroundColor Green
    }
}

# Directories to include
$includeDirs = @(
    "admin",
    "assets",
    "css",
    "includes",
    "js",
    "languages",
    "templates",
    "views"
)

foreach ($dir in $includeDirs) {
    if (Test-Path $dir) {
        Write-Host "  Copying $dir/..." -ForegroundColor Gray
        Copy-Item -Path $dir -Destination $pluginDir -Recurse -Force
    }
}

# Copy essential docs only
Write-Host "  Copying essential documentation..." -ForegroundColor Gray
if (!(Test-Path "$pluginDir\docs")) {
    New-Item -ItemType Directory -Path "$pluginDir\docs" -Force | Out-Null
}
Copy-Item -Path "START_HERE.md" -Destination "$pluginDir\" -Force -ErrorAction SilentlyContinue

# Copy deployment checklist if it exists
if (Test-Path "docs\deployment") {
    Copy-Item -Path "docs\deployment\DEPLOYMENT_CHECKLIST.md" -Destination "$pluginDir\docs\" -Force -ErrorAction SilentlyContinue
}

# Validate PHP files
Write-Host "`nValidating PHP files..." -ForegroundColor Cyan
$phpFiles = Get-ChildItem -Path $pluginDir -Filter "*.php" -Recurse
$errors = 0
foreach ($file in $phpFiles) {
    $result = php -l $file.FullName 2>&1
    if ($result -notmatch "No syntax errors") {
        Write-Host "  ERROR in $($file.Name)" -ForegroundColor Red
        $errors++
    }
}

if ($errors -gt 0) {
    Write-Host "`nBUILD FAILED: $errors PHP files have syntax errors" -ForegroundColor Red
    exit 1
}

Write-Host "  All PHP files validated ($($phpFiles.Count) files)" -ForegroundColor Green

# Create ZIP archive
Write-Host "`nCreating ZIP archive..." -ForegroundColor Cyan
Compress-Archive -Path $packageDir\* -DestinationPath $zipFile -Force

# Get file size
$zipSize = (Get-Item $zipFile).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  PRODUCTION BUILD COMPLETE!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "  Package:  $zipFile" -ForegroundColor White
Write-Host "  Size:     $zipSizeMB MB" -ForegroundColor White
Write-Host "  Files:    $($phpFiles.Count) PHP files validated" -ForegroundColor White
Write-Host ""
Write-Host "  Ready for deployment to WordPress!" -ForegroundColor Cyan
Write-Host ""

# Cleanup temp directory
Write-Host "Cleaning up temporary files..." -ForegroundColor Yellow
Remove-Item -Path $packageDir -Recurse -Force

Write-Host "Done!`n" -ForegroundColor Green
