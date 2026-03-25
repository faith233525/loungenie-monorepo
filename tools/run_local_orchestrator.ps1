<#
.SYNOPSIS
  Secure local orchestrator wrapper for Loungenie repo.

.DESCRIPTION
  Prompts to store FTP and WP credentials securely (Export-Clixml encrypted
  to the current user) and invokes the existing local orchestrator
  (tools/run_everything_local.ps1) in dry-run by default. Use -Live to
  enable live writes. Use -StoreCredentials the first time to save creds.

.NOTES
  - This script never sends or logs secrets to remote services.
  - Do NOT paste secrets into chat; store them locally via this script.
#>

[CmdletBinding()]
param(
    [switch]$StoreCredentials,
    [switch]$Live,
    [string]$CredDir,
    [switch]$UseCredentialManager,
    [switch]$FullRun,
    [switch]$AllowUntrustedFtpCert
)

function Ensure-CredDir {
    param($Path)
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

$ScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
if (-not $CredDir) { $CredDir = Join-Path $env:USERPROFILE '.config\loungenie' }
Ensure-CredDir -Path $CredDir

$ftpFile = Join-Path $CredDir 'ftp_cred.xml'
$wpFile = Join-Path $CredDir 'wp_cred.xml'

function Set-FileAclToCurrentUser {
    param($FilePath)
    try {
        $acl = Get-Acl -Path $FilePath
        $acl.SetAccessRuleProtection($true, $false)
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($env:USERNAME, 'FullControl', 'Allow')
        $acl.SetAccessRule($rule)
        Set-Acl -Path $FilePath -AclObject $acl
    }
    catch {
        Write-Verbose "Could not set ACL on ${FilePath}: $_"
    }
}

if ($StoreCredentials) {
    Write-Host "Storing credentials locally (encrypted to your user account)."
    $ftpUser = Read-Host 'FTP username (e.g. copilot@loungenie.com)'
    $ftpPass = Read-Host 'FTP password' -AsSecureString
    $ftpCred = New-Object System.Management.Automation.PSCredential($ftpUser, $ftpPass)
    $ftpCred | Export-Clixml -Path $ftpFile -Force
    Set-FileAclToCurrentUser -FilePath $ftpFile

    $wpUser = Read-Host 'WP username (e.g. copilot)'
    $wpPass = Read-Host 'WP application password (paste it when prompted)' -AsSecureString
    $wpCred = New-Object System.Management.Automation.PSCredential($wpUser, $wpPass)
    $wpCred | Export-Clixml -Path $wpFile -Force
    Set-FileAclToCurrentUser -FilePath $wpFile

    Write-Host "Credentials saved to: $CredDir"
    Write-Host "You can now run this script without interactive prompts."
    exit 0
}

if (-not (Test-Path $ftpFile) -or -not (Test-Path $wpFile)) {
    Write-Host "No stored credentials found. Run with -StoreCredentials to save them locally."
    exit 1
}

try {
    $ftpCred = Import-Clixml -Path $ftpFile
    $wpCred = Import-Clixml -Path $wpFile
}
catch {
    Write-Error "Failed to import stored credentials: $_"
    exit 1
}

function SecureString-ToPlainText {
    param([System.Security.SecureString]$s)
    $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
    try { [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr) }
    finally { [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
}

$env:FTP_USER = $ftpCred.UserName
$env:FTP_PASS = SecureString-ToPlainText -s $ftpCred.Password
$env:WP_USER = $wpCred.UserName
$env:WP_PASS = SecureString-ToPlainText -s $wpCred.Password

Write-Host "Running local orchestrator in" -NoNewline
if ($Live) { Write-Host "LIVE mode (writes enabled)." -ForegroundColor Yellow } else { Write-Host "dry-run mode (no writes)." -ForegroundColor Green }

# locate existing local orchestrator
$localOrch = Join-Path $ScriptRoot 'run_everything_local.ps1'
if (-not (Test-Path $localOrch)) {
    $localOrch = Join-Path $ScriptRoot '..\tools\run_everything_local.ps1'
}
if (-not (Test-Path $localOrch)) {
    Write-Error "Could not find tools/run_everything_local.ps1. Please run the orchestrator you use locally or adjust this script."
    exit 1
}

# Invoke the existing orchestrator with -Live when requested
if ($Live) {
    & pwsh -NoProfile -ExecutionPolicy Bypass -File $localOrch -Live
}
else {
    & pwsh -NoProfile -ExecutionPolicy Bypass -File $localOrch
}
# Helper to run run_wp_ftp_and_rest.ps1 locally with safe defaults
# (Parameters merged into top-level param() to avoid a mid-file param() error)

function Prompt-IfMissing($name, [bool]$isSecret = $false) {
    $existing = (Get-Item -Path "env:$name" -ErrorAction SilentlyContinue).Value
    if ($existing) { return }
    if ($isSecret) {
        $s = Read-Host -AsSecureString "$name (enter value)"
        $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
        $plain = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) | Out-Null
        Set-Item -Path "env:$name" -Value $plain -Force
    }
    else {
        $val = Read-Host "$name (enter value)"
        Set-Item -Path "env:$name" -Value $val -Force
    }
}

Write-Output "Preparing to run run_wp_ftp_and_rest.ps1"

# If you prefer Windows Credential Manager, set $UseCredentialManager and implement retrieval here.
if ($UseCredentialManager) {
    Write-Output "Note: Credential Manager integration not implemented. Will prompt instead."
}

# Allow untrusted FTP certs for temporary runs if explicitly requested
if ($AllowUntrustedFtpCert) {
    Write-Output "ALLOW_UNTRUSTED_FTP_CERT set: HTTPS/FTPS certificate name mismatches will be allowed for this run."
    $env:ALLOW_UNTRUSTED_FTP_CERT = 'true'
}
else {
    if (-not (Get-Item -Path env:ALLOW_UNTRUSTED_FTP_CERT -ErrorAction SilentlyContinue)) {
        $env:ALLOW_UNTRUSTED_FTP_CERT = 'false'
    }
}

# Prompt for required values if not already set in environment
Prompt-IfMissing 'FTP_HOST'
Prompt-IfMissing 'FTP_USER'
Prompt-IfMissing 'FTP_PASS' -isSecret $true
Prompt-IfMissing 'WP_USER'
Prompt-IfMissing 'WP_PASS' -isSecret $true
Prompt-IfMissing 'STAGING_URL'

# Safety defaults for local runs (can be changed as needed)
if ($FullRun) {
    $env:DRY_RUN = 'false'
    $env:CREATE_DRAFT_ONLY = 'false'
    Write-Output "Full run requested: DRY_RUN=false, CREATE_DRAFT_ONLY=false"
}
else {
    $env:DRY_RUN = $env:DRY_RUN -or 'true'
    $env:CREATE_DRAFT_ONLY = $env:CREATE_DRAFT_ONLY -or 'true'
}
$env:RUN_FTP_BACKUP = $env:RUN_FTP_BACKUP -or 'true'
$env:RUN_AUDITS = $env:RUN_AUDITS -or 'true'

Write-Output "Environment prepared. Running orchestrator (logs -> run_wp_ftp_and_rest.log)"

pwsh -NoProfile -ExecutionPolicy Bypass -File .\run_wp_ftp_and_rest.ps1 *> run_wp_ftp_and_rest.log

Write-Output "--- Last 200 lines of run_wp_ftp_and_rest.log ---"
Get-Content .\run_wp_ftp_and_rest.log -Tail 200
