param(
    [string]$workflowName = 'Automated Staging Deploy'
)

# Determine current branch
try {
    $branch = (git rev-parse --abbrev-ref HEAD).Trim()
}
catch {
    Write-Error 'Unable to determine current git branch. Ensure git is available and you are in a repo.'
    exit 2
}

if (-not $branch) {
    Write-Error 'Empty branch name.'
    exit 2
}

Write-Output "Triggering workflow '$workflowName' on branch '$branch'..."

try {
    gh workflow run "$workflowName" --ref $branch
    if ($LASTEXITCODE -ne 0) {
        Write-Error "gh workflow run returned exit code $LASTEXITCODE"
        exit $LASTEXITCODE
    }
    Write-Output 'Workflow dispatch requested. Use `gh run list` to follow status.'
}
catch {
    Write-Error "Failed to dispatch workflow: $_"
    exit 3
}
