# 0011 · Dark mode with CSS variables and a small script

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** development team (Mohammed Raiyan Ahmed)

## Context

Users asked for a button in the top bar to switch between dark and light mode. [ADR 0004](0004-ui-blade-and-one-stylesheet.md) says the interface is Blade plus one plain stylesheet, with no build step. The switch has to work on every page (guests too), be remembered, and not flash white when a dark page loads.

## Options considered

1. **A CSS framework with a dark variant (for example Tailwind):** works, but adds a build step and a rewrite of every view. Against ADR 0004.
2. **Only follow the operating system (`prefers-color-scheme`):** no script at all, but the user has no button to choose.
3. **Store the choice on the user in the database:** survives devices, but guests have no account, and it needs a migration and a request on every click.
4. **CSS variables plus a `data-theme` attribute, choice kept in the browser (chosen).**

## Decision

We choose **option 4**. All colours in `public/css/pegal.css` are variables on `:root`; the dark palette only redefines them under `:root[data-theme="dark"]`. A few lines in the layout's `<head>` set the attribute before the page is drawn (stored choice first, otherwise the system setting). `public/js/theme.js` handles the click and stores `pegel-theme` in `localStorage`.

## Consequences

- Easier: no library, no build, one place per colour, and new pages follow the theme for free as long as they use the variables.
- Harder: the choice lives in one browser, not in the account. If storage is blocked the switch still works until the page is reloaded.
- A new colour written directly in a view or the stylesheet will not follow the theme; keep using the variables.
