## Summary
Describe the change and why it's needed.

## Checklist
- [ ] I have run `scripts/build_plugin_zip.py` and confirmed `dist/lg-block-patterns.zip` is updated (if plugin changes)
- [ ] Added relevant tests (if applicable)
- [ ] Assigned reviewer(s) (@faith233525 recommended for LG9 files)
- [ ] Added label `automated` if this PR should be auto-merged into `deploy-staging` once approved

## Deployment
- For deploy to staging: merge into `deploy-staging` (protected). The `auto-deploy-staging` workflow will run on merge.
- If you want the change to be deployed automatically, add the `automated` label and ensure at least one approval.

## Notes
Any special notes for reviewers or operations.
