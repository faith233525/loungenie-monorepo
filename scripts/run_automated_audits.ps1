# Run automated audits locally and save reports to artifacts/
# Usage: Open PowerShell in workspace root and run: .\scripts\run_automated_audits.ps1

$python = "$PWD\venv\Scripts\python.exe"
$artifacts = "$PWD\artifacts"
# Force UTF-8 for Python scripts on Windows consoles to avoid UnicodeEncodeError
$env:PYTHONIOENCODING = 'utf-8'
try { chcp 65001 > $null } catch { }
if (-not (Test-Path $artifacts)) { New-Item -ItemType Directory -Path $artifacts | Out-Null }

Write-Host "Running image audit..."
& $python audit_all_images.py 2>&1 | Tee-Object -FilePath "$artifacts\audit_all_images.txt"

Write-Host "Running color/contrast audit..."
& $python audit_colors.py 2>&1 | Tee-Object -FilePath "$artifacts\audit_colors.txt"

Write-Host "Checking broken references/links..."
& $python check_broken_references.py 2>&1 | Tee-Object -FilePath "$artifacts\check_broken_references.txt"

Write-Host "Audits complete. Reports saved to $artifacts"