# Investor Pages Editor SOP (Staging)

## Goal
Safely update images and text without breaking design/layout.

## Use These Locked Gutenberg Patterns
- `LGP Pattern - Hero Section` (ID 9079)
- `LGP Pattern - Board Member Card` (ID 9080)
- `LGP Pattern - Financial Filing List` (ID 9081)
- `LGP Pattern - Press Release List` (ID 9082)

## Editing Rules
1. Insert only the LGP patterns for new sections.
2. Replace text and images inside pattern content.
3. Do not remove pattern wrapper blocks.
4. Do not add inline styles unless required.

## Hero Image Process
1. Open page editor.
2. Right sidebar -> Featured Image.
3. Replace featured image there.
4. Keep hero dimensions around 1600x600.

## Board Member Photo Process
1. Insert `LGP Pattern - Board Member Card`.
2. Replace image in the card.
3. Update name, role, and bio text.
4. Keep portrait orientation (recommended 4:5).

## Financial Filings Process
1. Insert `LGP Pattern - Financial Filing List`.
2. Replace list item text and links with current filings.
3. Use full URLs with https.

## Press Releases Process
1. Insert `LGP Pattern - Press Release List`.
2. Replace headlines and links.
3. Keep newest release first.

## QA Before Publish
1. Preview Desktop and Mobile.
2. Check all links open correctly.
3. Confirm no placeholder `#` links.
4. Confirm text remains readable on dark/light sections.

## Rollback
1. Use page revisions to restore previous version.
2. Re-select previous featured image if needed.
