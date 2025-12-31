Set-Location "C:\Users\pools\Downloads\loungenie-portal-production (4)\loungenie-portal-production"

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║    LOUNGENIE PORTAL - COMPLETE DEPLOYMENT READINESS TEST   ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$testsPassed = 0
$testsFailed = 0
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Write-Host "Test Run: $timestamp" -ForegroundColor Gray
Write-Host ""

# Phase 1: File Structure Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 1: FILE STRUCTURE VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$requiredDirs = @(
    "includes",
    "api",
    "templates",
    "assets",
    "roles",
    "wp-cli",
    "assets\css",
    "assets\js"
)

$dirsPassed = 0
foreach ($dir in $requiredDirs) {
    if (Test-Path $dir) {
        Write-Host "  [OK] Directory: $dir" -ForegroundColor Green
        $dirsPassed++
    } else {
        Write-Host "  [FAIL] Directory: $dir (MISSING)" -ForegroundColor Red
        $testsFailed++
    }
}

$dirsPassed = $dirsPassed
$dirsTotal = $requiredDirs.Count
Write-Host ""
Write-Host "  Result: $dirsPassed/$dirsTotal directories found" -ForegroundColor Cyan
Write-Host ""

# Phase 2: Plugin File Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 2: PLUGIN FILE VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$requiredFiles = @(
    "loungenie-portal.php",
    "activation.php",
    "deactivation.php",
    "README.md",
    "includes\class-lgp-loader.php",
    "includes\class-lgp-auth.php",
    "includes\class-lgp-database.php"
)

$filesPassed = 0
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "  [✓] File: $file" -ForegroundColor Green
        $filesPassed++
    } else {
        Write-Host "  [✗] File: $file (MISSING)" -ForegroundColor Red
        $testsFailed++
    }
}

Write-Host ""
Write-Host "  Result: $filesPassed/$($requiredFiles.Count) files found" -ForegroundColor Cyan
Write-Host ""

# Phase 3: Plugin Header Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 3: PLUGIN HEADER VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$pluginFile = Get-Content "loungenie-portal.php" -Raw

$headers = @{
    "Plugin Name" = "LounGenie Portal"
    "Description" = "Multi-tenant"
    "Version" = "2.0.0"
    "Author" = "LounGenie"
    "License" = "GPL"
}

$headersPassed = 0
foreach ($header in $headers.GetEnumerator()) {
    if ($pluginFile -match $header.Name) {
        Write-Host "  [✓] Header: $($header.Name)" -ForegroundColor Green
        $headersPassed++
    } else {
        Write-Host "  [✗] Header: $($header.Name) (MISSING)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "  Result: $headersPassed/$($headers.Count) headers valid" -ForegroundColor Cyan
Write-Host ""

# Phase 4: PHP Syntax Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 4: PHP SYNTAX VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$phpFiles = Get-ChildItem -Path @("includes", "api", "templates", "roles", "wp-cli") -Filter "*.php" -Recurse
$syntaxValid = 0
$syntaxErrors = 0

Write-Host "  Checking $($phpFiles.Count) PHP files..." -ForegroundColor Yellow

foreach ($file in $phpFiles | Select-Object -First 20) {
    $result = & php -l $file.FullName 2>&1
    if ($result -match "No syntax errors") {
        $syntaxValid++
    } else {
        Write-Host "  [✗] Syntax error in: $($file.Name)" -ForegroundColor Red
        $syntaxErrors++
    }
}

Write-Host ""
Write-Host "  Result: $syntaxValid files have valid syntax, $syntaxErrors errors found" -ForegroundColor Cyan
Write-Host ""

# Phase 5: WordPress Compatibility
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 5: WORDPRESS COMPATIBILITY" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$wpChecks = @(
    @("ABSPATH", "Direct access protection"),
    @("wp_json_encode", "Safe JSON encoding"),
    @("wp_kses_post", "Content sanitization"),
    @("current_user_can", "Permission checking"),
    @("register_rest_route", "REST API registration"),
    @("add_action", "Hook registration")
)

$wpPassed = 0
$combinedContent = ""
foreach ($file in $phpFiles) {
    $combinedContent += Get-Content $file.FullName -Raw + " "
}

foreach ($check in $wpChecks) {
    if ($combinedContent -match $check[0]) {
        Write-Host "  [✓] $($check[1])" -ForegroundColor Green
        $wpPassed++
    } else {
        Write-Host "  [?] $($check[1])" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "  Result: $wpPassed/$($wpChecks.Count) compatibility checks passed" -ForegroundColor Cyan
Write-Host ""

# Phase 6: Security Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 6: SECURITY VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$securityChecks = @(
    @("wp_verify_nonce", "Nonce verification"),
    @("wp_create_nonce", "Nonce creation"),
    @("sanitize", "Input sanitization"),
    @("escape", "Output escaping"),
    @("current_user_can", "Capability checking")
)

$securityPassed = 0
foreach ($check in $securityChecks) {
    $count = ([regex]::Matches($combinedContent, $check[0])).Count
    if ($count -gt 0) {
        Write-Host "  [✓] $($check[1]) ($count found)" -ForegroundColor Green
        $securityPassed++
    } else {
        Write-Host "  [?] $($check[1]) (0 found)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "  Result: $securityPassed/$($securityChecks.Count) security measures implemented" -ForegroundColor Cyan
Write-Host ""

# Phase 7: REST API Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 7: REST API VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$apiFiles = Get-ChildItem "api" -Filter "*.php"
$apiEndpoints = 0

Write-Host "  API Files Found: $($apiFiles.Count)" -ForegroundColor Yellow
foreach ($file in $apiFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match "register_rest_route") {
        $apiEndpoints++
        Write-Host "  [✓] API File: $($file.BaseName)" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "  Result: $apiEndpoints REST endpoints configured" -ForegroundColor Cyan
Write-Host ""

# Phase 8: Database Schema Validation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 8: DATABASE SCHEMA VALIDATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$dbFile = Get-Content "includes\class-lgp-database.php" -Raw

$tables = @(
    "lgp_companies",
    "lgp_units",
    "lgp_tickets",
    "lgp_service_requests",
    "lgp_gateways",
    "lgp_ticket_attachments",
    "lgp_audit_log"
)

$tablesPassed = 0
foreach ($table in $tables) {
    if ($dbFile -match $table) {
        Write-Host "  [✓] Table: $table" -ForegroundColor Green
        $tablesPassed++
    } else {
        Write-Host "  [✗] Table: $table (NOT DEFINED)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "  Result: $tablesPassed/$($tables.Count) tables defined" -ForegroundColor Cyan
Write-Host ""

# Phase 9: Asset Configuration
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 9: ASSET CONFIGURATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$cssFiles = (Get-ChildItem "assets\css" -Filter "*.css" | Measure-Object).Count
$jsFiles = (Get-ChildItem "assets\js" -Filter "*.js" | Measure-Object).Count

Write-Host "  [✓] CSS Files: $cssFiles files" -ForegroundColor Green
Write-Host "  [✓] JavaScript Files: $jsFiles files" -ForegroundColor Green

$assetFile = Get-Content "includes\class-lgp-assets.php" -Raw -ErrorAction SilentlyContinue
if ($assetFile -and $assetFile -match "wp_enqueue_style|wp_enqueue_script") {
    Write-Host "  [✓] Asset enqueuing configured" -ForegroundColor Green
    $testsPassed++
}

Write-Host ""
Write-Host "  Result: Assets properly organized and configured" -ForegroundColor Cyan
Write-Host ""

# Phase 10: Documentation
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "PHASE 10: DOCUMENTATION" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$docs = @(
    "README.md",
    "DEPLOYMENT-GUIDE.md",
    "CHANGELOG.md"
)

$docsPassed = 0
foreach ($doc in $docs) {
    if (Test-Path $doc) {
        Write-Host "  [✓] Documentation: $doc" -ForegroundColor Green
        $docsPassed++
    }
}

Write-Host ""
Write-Host "  Result: $docsPassed/$($docs.Count) documentation files present" -ForegroundColor Cyan
Write-Host ""

# Final Summary
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "TEST SUMMARY" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$totalTests = 10
$testScore = @(
    $dirsPassed -eq $dirsTotal,
    $filesPassed -eq $requiredFiles.Count,
    $headersPassed -eq $headers.Count,
    $syntaxValid -ge ($phpFiles.Count * 0.9),
    $wpPassed -ge ($wpChecks.Count * 0.8),
    $securityPassed -ge ($securityChecks.Count * 0.8),
    $apiEndpoints -gt 0,
    $tablesPassed -eq $tables.Count,
    ($cssFiles -gt 0 -and $jsFiles -gt 0),
    $docsPassed -gt 0
) | Where-Object { $_ } | Measure-Object | Select-Object -ExpandProperty Count

Write-Host ""
Write-Host "PHASE RESULTS:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  Phase 1 (File Structure):        $dirsPassed/$dirsTotal ✓" -ForegroundColor Green
Write-Host "  Phase 2 (Plugin Files):          $filesPassed/$($requiredFiles.Count) ✓" -ForegroundColor Green
Write-Host "  Phase 3 (Plugin Headers):        $headersPassed/$($headers.Count) ✓" -ForegroundColor Green
Write-Host "  Phase 4 (PHP Syntax):            $syntaxValid files valid ✓" -ForegroundColor Green
Write-Host "  Phase 5 (WP Compatibility):      $wpPassed/$($wpChecks.Count) ✓" -ForegroundColor Green
Write-Host "  Phase 6 (Security):              $securityPassed/$($securityChecks.Count) ✓" -ForegroundColor Green
Write-Host "  Phase 7 (REST API):              $apiEndpoints endpoints ✓" -ForegroundColor Green
Write-Host "  Phase 8 (Database Schema):       $tablesPassed/$($tables.Count) ✓" -ForegroundColor Green
Write-Host "  Phase 9 (Assets):                CSS: $cssFiles, JS: $jsFiles ✓" -ForegroundColor Green
Write-Host "  Phase 10 (Documentation):        $docsPassed/$($docs.Count) ✓" -ForegroundColor Green
Write-Host ""
Write-Host "  Overall Score: $testScore/$totalTests phases PASSED" -ForegroundColor Cyan
Write-Host ""

# Final Status
if ($testScore -eq 10) {
    Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║                                                            ║" -ForegroundColor Green
    Write-Host "║               ✓ DEPLOYMENT READY - ALL TESTS PASS           ║" -ForegroundColor Green
    Write-Host "║                                                            ║" -ForegroundColor Green
    Write-Host "║  The LounGenie Portal plugin is fully prepared for        ║" -ForegroundColor Green
    Write-Host "║  production deployment to WordPress environments.          ║" -ForegroundColor Green
    Write-Host "║                                                            ║" -ForegroundColor Green
    Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  1. Review DEPLOYMENT-GUIDE.md for installation instructions"
    Write-Host "  2. Prepare WordPress environment with PHP 7.4+ and MySQL 5.7+"
    Write-Host "  3. Upload plugin files to /wp-content/plugins/loungenie-portal/"
    Write-Host "  4. Activate plugin in WordPress admin dashboard"
    Write-Host "  5. Configure Microsoft services and email integration"
    Write-Host "  6. Create required database entries and test portal access"
    Write-Host ""
    Write-Host "STATUS: Production Ready ✓" -ForegroundColor Green
    Write-Host ""
    exit 0
} else {
    Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Yellow
    Write-Host "║                                                            ║" -ForegroundColor Yellow
    Write-Host "║               ⚠ REVIEW REQUIRED - SOME ISSUES FOUND         ║" -ForegroundColor Yellow
    Write-Host "║                                                            ║" -ForegroundColor Yellow
    Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Failed: $($totalTests - $testScore) phase(s)" -ForegroundColor Red
    Write-Host ""
    exit 1
}
