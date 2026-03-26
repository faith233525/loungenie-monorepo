# Branch Usage Guide for cPanel Deployment

## Current cPanel Configuration

Your cPanel staging server is currently configured as:
- **Repository Path**: `/home/pools425/repositories/loungenie-stage`
- **Remote URL**: `https://github.com/faith233525/loungenie-monorepo.git`
- **Currently Checked-Out Branch**: `main`
- **Current HEAD Commit**: `7615985` (fix: Add delay between page updates to reduce API strain)

## Which Branch Should You Use?

### ✅ Use `main` Branch (RECOMMENDED)

**You should continue using the `main` branch** for your cPanel staging deployment because:

1. **Stable Code**: The `main` branch contains stable, tested code (commit `7615985`)
2. **Current Configuration**: Your cPanel is already configured for `main` 
3. **Production-Ready**: This includes all the fixes for page updates and API reliability

### Current Branch Status

- **`main`** ← USE THIS for cPanel staging
  - Latest commit: `7615985` - "fix: Add delay between page updates to reduce API strain"
  - Contains all production-ready code
  - Includes REST API timeout improvements and retry logic

- **`copilot/fix-delay-between-page-updates`** (feature branch)
  - Latest commit: `1c1a9cc` - "feat: Add deployment setup test workflow"
  - Contains test workflow for validating deployment configuration
  - Should be merged to `main` after testing

## Deployment Workflow

### For cPanel Staging Deployment:

1. **Keep using `main` branch** in cPanel Git interface
2. When you want to deploy new changes:
   - Go to cPanel → Git Version Control
   - Click "Pull" or "Update from Remote"
   - It will pull the latest `main` branch changes

### For Testing New Features:

1. New features are developed in feature branches (like `copilot/fix-delay-between-page-updates`)
2. Once tested and approved, they get merged into `main`
3. Then you can pull `main` to cPanel staging

## Quick Reference

```
Your cPanel Setup:
├── Branch: main ✓
├── Commit: 7615985 ✓
└── Ready to pull updates from main ✓
```

## Summary

**Answer: Continue using the `main` branch in your cPanel configuration.** 

This is already correctly set up. When you want to deploy updates, simply pull from the `main` branch using the cPanel Git interface.
