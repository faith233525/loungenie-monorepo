Param(
    [string]$User = 'Copilot',
    [string]$PlainPassword = 'OvBU Tifi iSpG CeaG 10mk xoty'
)

$credDir = Join-Path $env:USERPROFILE '.config\loungenie'
if (-not (Test-Path $credDir)) { New-Item -ItemType Directory -Path $credDir -Force | Out-Null }
$wpFile = Join-Path $credDir 'wp_cred.xml'

$secure = ConvertTo-SecureString -String $PlainPassword -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($User, $secure)

try {
    $cred | Export-Clixml -Path $wpFile -Force
    Write-Output "Rewrote WP cred to $wpFile"
} catch {
    Write-Error "Failed to write WP cred: $($_.Exception.Message)"
    exit 1
}
