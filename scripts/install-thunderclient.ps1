<#
install-thunderclient.ps1

Installs the Thunder Client VS Code extension (`rangav.vscode-thunder-client`).
- If `code` (VS Code CLI) is not found, the script can optionally install VS Code via `winget` when run elevated.

Usage:
  # Install extension (requires `code` on PATH)
  pwsh -ExecutionPolicy Bypass -File .\scripts\install-thunderclient.ps1

  # If `code` isn't available and you want the script to install VS Code (Windows), run elevated:
  pwsh -ExecutionPolicy Bypass -File .\scripts\install-thunderclient.ps1 -InstallVSCode

Notes:
- On Linux/macOS, install VS Code yourself and ensure `code` is on PATH.
- This script does not send any secrets anywhere.
#>

param(
    [switch]$InstallVSCode  # try to install VS Code via winget if `code` not found (Windows only)
)

function Find-CodeCLI {
    $cmd = Get-Command code -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    # Common Windows install locations
    $candidates = @(
        "$env:LOCALAPPDATA\Programs\Microsoft VS Code\bin\code.cmd",
        "$env:ProgramFiles\Microsoft VS Code\bin\code.cmd",
        "$env:ProgramFiles(x86)\Microsoft VS Code\bin\code.cmd"
    )
    foreach ($p in $candidates) {
        if (Test-Path $p) { return $p }
    }
    return $null
}

Write-Host "Checking for VS Code 'code' CLI..."
$codePath = Find-CodeCLI
if (-not $codePath) {
    Write-Host "'code' CLI not found on PATH."
    if ($InstallVSCode) {
        if ($IsWindows) {
            Write-Host "Attempting to install Visual Studio Code via winget (requires elevated shell)..."
            try {
                winget install --id Microsoft.VisualStudioCode -e --source winget -h
            } catch {
                Write-Error "winget install failed: $_"
                exit 2
            }
            Start-Sleep -Seconds 3
            $codePath = Find-CodeCLI
            if (-not $codePath) {
                Write-Error "Installed VS Code but 'code' CLI still not found. Please ensure VS Code is added to PATH or restart your shell."
                exit 3
            }
        } else {
            Write-Error "Automatic VS Code installation via winget is only supported on Windows. Please install VS Code manually and ensure 'code' is on PATH."
            exit 4
        }
    } else {
        Write-Error "Please install VS Code and ensure the 'code' command is available on PATH, or re-run with -InstallVSCode on Windows."
        exit 1
    }
}

Write-Host "Found 'code' at: $codePath"

# Install Thunder Client extension
$extId = 'rangav.vscode-thunder-client'
Write-Host "Installing Thunder Client extension ($extId)..."
try {
    & $codePath --install-extension $extId --force
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Thunder Client installed successfully."
        Write-Host "Open VS Code and press Ctrl+Shift+P → 'Thunder Client: New Request' to get started."
        exit 0
    } else {
        Write-Error "'code' CLI returned exit code $LASTEXITCODE. Check VS Code and try installing the extension manually."
        exit $LASTEXITCODE
    }
} catch {
    Write-Error "Failed to run 'code' CLI: $_"
    exit 5
}
