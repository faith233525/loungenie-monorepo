#!/usr/bin/env pwsh
<#
Save the provided Copilot WP app password encrypted to the current user's profile
and run the apply_page_4862.ps1 script to apply the About page.
#>

$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
if (-not (Test-Path $credDir)) { New-Item -ItemType Directory -Path $credDir -Force | Out-Null }

$wpUser = 'Copilot'
$wpPassPlain = 'OvBU Tifi iSpG CeaG 10mk xoty'
$secure = ConvertTo-SecureString -String $wpPassPlain -AsPlainText -Force
$wpCred = New-Object System.Management.Automation.PSCredential($wpUser, $secure)
$wpFile = Join-Path $credDir 'wp_cred.xml'

try {
    $wpCred | Export-Clixml -Path $wpFile -Force
    # Restrict file ACL to current user
    try {
        $acl = Get-Acl -Path $wpFile
        $acl.SetAccessRuleProtection($true, $false)
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($env:USERNAME, 'FullControl', 'Allow')
        $acl.SetAccessRule($rule)
        Set-Acl -Path $wpFile -AclObject $acl
    } catch { Write-Warning ("Could not set ACL on " + $wpFile + ": " + $_.Exception.Message) }

    Write-Output "Saved encrypted WP credential to: $wpFile"
} catch {
    Write-Error ("Failed to save WP credential: " + $_.Exception.Message)
    exit 1
}

# Export env vars for this session and run the apply script
$env:WP_USER = $wpUser
$env:WP_PASS = $wpPassPlain
$env:WP_SITE_URL = 'https://loungenie.com/staging'

Write-Output 'Environment variables set for this session. Running apply_page_4862.ps1...'
try {
    & .\tools\apply_page_4862.ps1
} catch {
    Write-Error ("apply_page_4862.ps1 failed: " + $_.Exception.Message)
    exit 1
}
