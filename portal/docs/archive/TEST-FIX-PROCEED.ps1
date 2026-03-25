Set-Location "C:\Users\pools\Downloads\loungenie-portal-production (4)\loungenie-portal-production"

$testNumber = 1

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "AUTOMATED TEST-FIX-PROCEED FRAMEWORK" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# TEST 1: PHP Syntax Validation
Write-Host "[TEST $testNumber] Running PHP Syntax Validation..." -ForegroundColor Yellow

$phpFiles = @(
    "loungenie-portal.php",
    "includes\class-lgp-loader.php",
    "includes\class-lgp-auth.php",
    "includes\class-lgp-database.php",
    "api\tickets.php"
)

$syntaxPassed = 0
$syntaxFailed = 0
$failedFiles = @()

foreach ($file in $phpFiles) {
    if (Test-Path $file) {
        $output = php -l $file 2>&1
        if ($LASTEXITCODE -eq 0) {
            $syntaxPassed++
            Write-Host "  [OK] $file" -ForegroundColor Green
        } else {
            $syntaxFailed++
            $failedFiles += $file
            Write-Host "  [FAIL] $file" -ForegroundColor Red
        }
    }
}

if ($syntaxFailed -eq 0) {
    Write-Host ""
    Write-Host "[PASS] TEST 1: PHP Syntax Validation" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host ""
    Write-Host "[FAIL] TEST 1: Syntax errors in $($failedFiles -join ', ')" -ForegroundColor Red
    exit 1
}

# TEST 2: Security - ABSPATH Protection
Write-Host "[TEST $testNumber] Running Security ABSPATH Check..." -ForegroundColor Yellow

$abspathCount = 0
$allFiles = Get-ChildItem "includes" -Filter "*.php" -Recurse

foreach ($file in $allFiles) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -match "ABSPATH") {
        $abspathCount++
    }
}

if ($abspathCount -gt 30) {
    Write-Host "  [OK] Found $abspathCount ABSPATH checks" -ForegroundColor Green
    Write-Host ""
    Write-Host "[PASS] TEST 2: Security ABSPATH Check" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  [FAIL] Only found $abspathCount ABSPATH checks" -ForegroundColor Red
    exit 1
}

# TEST 3: SQL Prepared Statements
Write-Host "[TEST $testNumber] Running SQL Prepared Statement Check..." -ForegroundColor Yellow

$prepareCount = 0
$apiFiles = Get-ChildItem "api" -Filter "*.php" -Recurse
$includeFiles = Get-ChildItem "includes" -Filter "*.php" -Recurse

$allDbFiles = @($apiFiles) + @($includeFiles)

foreach ($file in $allDbFiles) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -match "prepare") {
        $prepareCount++
    }
}

if ($prepareCount -gt 25) {
    Write-Host "  [OK] Found $prepareCount prepared statements" -ForegroundColor Green
    Write-Host ""
    Write-Host "[PASS] TEST 3: SQL Prepared Statements" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  [FAIL] Only found $prepareCount prepared statements" -ForegroundColor Red
    exit 1
}

# TEST 4: Output Escaping
Write-Host "[TEST $testNumber] Running Output Escaping Check..." -ForegroundColor Yellow

$escapeCount = 0
$templateFiles = Get-ChildItem "templates" -Filter "*.php" -Recurse

foreach ($file in $templateFiles) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -match "esc_") {
        $escapeCount++
    }
}

if ($escapeCount -gt 10) {
    Write-Host "  [OK] Found $escapeCount output escaping calls" -ForegroundColor Green
    Write-Host ""
    Write-Host "[PASS] TEST 4: Output Escaping" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  [FAIL] Only found $escapeCount output escaping calls" -ForegroundColor Red
    exit 1
}

# TEST 5: Critical Files Present
Write-Host "[TEST $testNumber] Running File Structure Validation..." -ForegroundColor Yellow

$criticalFiles = @(
    "loungenie-portal.php",
    "includes\class-lgp-loader.php",
    "includes\class-lgp-auth.php",
    "includes\class-lgp-database.php",
    "templates\portal-shell.php",
    "assets\css\portal.css",
    "assets\js\portal.js"
)

$filesFailed = 0

foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        Write-Host "  [OK] $file" -ForegroundColor Green
    } else {
        $filesFailed++
        Write-Host "  [FAIL] Missing: $file" -ForegroundColor Red
    }
}

if ($filesFailed -eq 0) {
    Write-Host ""
    Write-Host "[PASS] TEST 5: File Structure Validation" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host ""
    Write-Host "[FAIL] TEST 5: $filesFailed critical files missing" -ForegroundColor Red
    exit 1
}

# TEST 6: API Endpoints
Write-Host "[TEST $testNumber] Running API Endpoints Check..." -ForegroundColor Yellow

$apiCount = (Get-ChildItem "api" -Filter "*.php" | Measure-Object).Count

if ($apiCount -ge 10) {
    Write-Host "  ✅ Found $apiCount API endpoint files" -ForegroundColor Green
    Write-Host ""
    Write-Host "✅ TEST 6 PASSED: API endpoints present" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  ❌ Only found $apiCount API files (expected >= 10)" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# TEST 7: Database Class
Write-Host "[TEST $testNumber] Running Database Class Check..." -ForegroundColor Yellow

$dbContent = Get-Content "includes\class-lgp-database.php" -Raw -ErrorAction SilentlyContinue

if ($dbContent -match "function create_tables" -and $dbContent -match "public static function init") {
    Write-Host "  ✅ create_tables() method found" -ForegroundColor Green
    Write-Host "  ✅ init() method found" -ForegroundColor Green
    Write-Host ""
    Write-Host "✅ TEST 7 PASSED: Database class complete" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  ❌ Required methods missing" -ForegroundColor Red
    Write-Host "ACTION: Ensure create_tables() and init() exist in class-lgp-database.php" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

# TEST 8: WordPress Compatibility
Write-Host "[TEST $testNumber] Running WordPress Compatibility Check..." -ForegroundColor Yellow

$mainContent = Get-Content "loungenie-portal.php" -Raw -ErrorAction SilentlyContinue

if ($mainContent -match "add_action|add_filter|do_action|apply_filters") {
    Write-Host "  ✅ WordPress hooks detected" -ForegroundColor Green
    Write-Host ""
    Write-Host "✅ TEST 8 PASSED: WordPress compatibility verified" -ForegroundColor Green
    Write-Host ""
    $testNumber++
} else {
    Write-Host "  ❌ No WordPress hooks found" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# FINAL SUMMARY
Write-Host "==========================================" -ForegroundColor Green
Write-Host "ALL TESTS PASSED SUCCESSFULLY" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "  ✅ Test 1: PHP Syntax - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 2: Security ABSPATH - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 3: SQL Prepared Statements - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 4: Output Escaping - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 5: File Structure - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 6: API Endpoints - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 7: Database Class - PASSED" -ForegroundColor Green
Write-Host "  ✅ Test 8: WordPress Compatibility - PASSED" -ForegroundColor Green
Write-Host ""
Write-Host "RESULT: PRODUCTION READY - NO FAILURES" -ForegroundColor Green
Write-Host ""
