# Staging Investor Pages Mobile QA Checklist

Date: 2026-03-23
Target: https://loungenie.com/staging
Scope: /investors/, /board/, /financials/, /press/

## Content Cleanup Status

- Investors raw content: pattern starter removed
- Press raw content: pattern starter removed
- Rendered HTML probe: no visible `Pattern Starter (Editor Only)` text on any investor page
- Rendered HTML probe: no visible `GET STARTED` text on any investor page

## Structured Mobile QA Checklist

### Shared Checks

- [pass] Page loads on staging URL and resolves with investor-specific hero/title
- [pass] No visible pattern starter/editor placeholder text in fetched HTML
- [pass] Footer investor PDF link resolves to the corrected staging 2026 notice PDF
- [needs manual viewport test] Header/logo/request-demo row stays readable at 320px to 430px widths
- [needs manual viewport test] Sticky header does not overlap hero heading during scroll
- [needs manual viewport test] Tap targets in top navigation and footer remain comfortably tappable
- [needs manual viewport test] CTA buttons wrap cleanly without collision or clipping
- [needs manual viewport test] Footer columns stack in a sensible order with readable spacing

### Investors Page

- URL: https://loungenie.com/staging/investors/
- [pass] Main sections are present: Corporate Information, Transfer Agent, Governance & Compliance, Investor Relations Contact, Legal Counsel
- [pass] Primary actions are limited and clear: Latest Financials, Latest Press, Request a Demo
- [risk] Corporate address and transfer-agent text are dense and may wrap awkwardly on narrow screens
- [risk] Governance/compliance list includes inline icon-text rows that should be checked for line breaks and spacing
- [needs manual viewport test] Phone and email links should be tested for tap accuracy and spacing
- [needs manual viewport test] The investor shortcut links under the hero should be checked for multi-line wrapping

### Board Page

- URL: https://loungenie.com/staging/board/
- [pass] All required board members remain visible in fetched content
- [pass] Hero, intro copy, board directory, and footer CTA are present
- [risk] This page has the longest bio blocks; paragraph readability and vertical rhythm matter more than on other investor pages
- [risk] The hero shortcut cluster contains six compact action links and may feel crowded on small screens
- [needs manual viewport test] Check that profile images, names, titles, and bios keep a clean reading order with no overlap
- [needs manual viewport test] Confirm there is enough spacing between adjacent leadership cards when stacked vertically

### Financials Page

- URL: https://loungenie.com/staging/financials/
- [pass] Required Filing Index remains visible
- [pass] 2026 special meeting and historical filing sections remain visible
- [pass] Q2 2025 MD&A now points to the corrected staging PDF
- [risk] This page is link-dense and likely the highest mobile scanning burden after Press
- [risk] Long filing labels may wrap into two or three lines, especially within yearly groups
- [needs manual viewport test] Verify each PDF link row preserves separation between Financials and MD&A links
- [needs manual viewport test] Confirm year-section headings remain sticky in context visually and do not collapse into dense blocks

### Press Page

- URL: https://loungenie.com/staging/press/
- [pass] No visible pattern starter content remains in fetched HTML
- [pass] Top press items now use staging-host PDF links in rendered HTML
- [risk] This is the longest page by far and will need the heaviest scroll endurance check on mobile
- [risk] Repeated release title, excerpt, and Read More pattern may create fatigue if spacing collapses on small screens
- [needs manual viewport test] Check Read More links for comfortable tap spacing between entries
- [needs manual viewport test] Confirm long headlines wrap cleanly and do not create orphaned one-word lines excessively

## Recommended Manual Device Pass

- iPhone-width portrait: 390px
- Narrow Android portrait: 360px
- Small landscape pass: 740px to 812px wide

## Manual Test Sequence

1. Open each staging investor page on a real mobile viewport.
2. Check first-screen readability before any scroll.
3. Scroll through the full page and watch for overlap, clipped buttons, or cramped link groups.
4. Tap primary actions, investor shortcut links, and footer investor links.
5. Open at least one PDF link from each page section group.
6. Recheck footer stack order and CTA spacing at the bottom of each page.

## Current Conclusion

- The editor-only pattern starter blocks are removed from the staging investor pages.
- Source and fetched HTML now look clean.
- Remaining work is a real viewport/tap pass for spacing, wrapping, and tap-target validation.