Create & Push Repository — Instructions

These steps let you create a GitHub repository from this workspace and push the current files.

Prereqs:
- `git` installed and configured (name/email)
- `gh` (GitHub CLI) installed and authenticated (run `gh auth login`)

Quick steps (PowerShell):

```powershell
# 1) (Optional) Authenticate or ensure gh access
gh auth status || gh auth login

# 2) Choose a repo name (default used by script)
$RepoName = 'loungenie-update-pages'

# 3) (Optional) Export secrets to set in GitHub (the script can set them):
$env:WP_USERNAME = "your-wp-username"
$env:WP_APP_PASSWORD = "your-app-password"
$env:WP_SITE_URL = "https://loungenie.com/staging"

# 4) Run the helper script (creates repo, commits, pushes, sets secrets if env vars present)
.\create_repo.ps1 -RepoName $RepoName -Visibility public -SetSecrets

# 5) Verify: Open https://github.com/<your-username>/$RepoName and go to the Actions tab.
```

Manual alternative (if you prefer to control steps):

```powershell
# init, commit, create remote, push
git init
git add -A
git commit -m "Initial commit"
gh repo create $RepoName --public --source=. --remote=origin --push

# set secrets (interactive)
gh secret set WP_USERNAME --body "$env:WP_USERNAME"
gh secret set WP_APP_PASSWORD --body "$env:WP_APP_PASSWORD"
gh secret set WP_SITE_URL --body "$env:WP_SITE_URL"
```

Notes:
- Do not paste secrets into chat. Use environment variables and `gh secret set`.
- After pushing, go to the repository Actions tab and trigger `Update WordPress Pages` via `workflow_dispatch` or by pushing to `main`.
- The workflow uses the `WP_*` secrets to run `scripts/update_pages.py`.
