# Copilot / Agent Instructions for this Workspace

Purpose
- Provide concise guidance for GitHub Copilot / AI agents working in this repo.
- Follow the "Link, don't embed" principle: reference docs, do not duplicate large content.

When to use
- Use for automated agents, pull-request assistants, and first-run onboarding.

Quick workflow
1. Discover existing conventions
   - Look for project docs and config files (README.md, docs/, .github/, build scripts).
2. Explore the codebase
   - Identify build/test commands, key directories, and integration points.
3. Generate or merge instructions
   - If a top-level agent doc is missing, create/update `.github/copilot-instructions.md`.
   - Prefer adding links to existing READMEs instead of embedding long content.
4. Iterate
   - Ask maintainers for clarification; add applyTo patterns if parts differ (frontend/backend).

Principles
- Be conservative: avoid breaking changes; prefer non-destructive suggestions.
- Respect secrets: never request or embed credentials in files or chat.
- Preserve style: follow existing formatting and conventions in the repo.
- Minimal edits: change only what's needed to implement an instruction.

Templates & Examples
- Use the workflow above as a template for new agent instructions.
- Example prompt to run against the repo:
  - "List build/test commands and where they are defined."
  - "Suggest a small non-breaking change to improve README clarity for new contributors."

Suggested next agent customizations
- `create-agent-hook`: small specialized agents for tasks like releasing, linting, or updating pages.
- `create-skill`: reusable skills for repository-specific tasks (e.g., WordPress page updates).

How to update this file
- Keep it short. Link to deeper docs (e.g., docs/ or READMEs) for details.
- If a new workflow or convention is added, update this file and add a one-line changelog entry.

Example references in this repo
- See [page-templates/README.md](page-templates/README.md) for template examples.
- See [tools/loungenie-block-patterns/README.md](tools/loungenie-block-patterns/README.md) for pattern guidance.

If anything here is unclear, request clarification from a repository maintainer before making large changes.