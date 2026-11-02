# 0004 · UI: Blade templates and plain CSS, no frontend build

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

The jury must start the product from the README in 5 minutes. Laravel's starter setup uses Vite and Tailwind, which means Node, `npm install` and `npm run build` before any page looks right. We also have under two days left, and the screens we need are forms, tables and a budget gauge.

## Options considered

1. **Blade + Vite + Tailwind (Laravel default)** – nice tooling, but adds Node as a prerequisite and a build step that can fail on the jury's machine.
2. **Blade + one plain CSS file in `public/css`** – nothing to build, works the moment PHP runs. Less convenient for large UIs.
3. **Separate single-page app (Vue or React)** – rich UI, but a second project, a second set of tests and more setup.

## Decision

We choose **option 2**: server-rendered Blade views and a single hand-written stylesheet, `public/css/pegal.css`. The budget gauge is drawn with plain HTML and CSS.

## Consequences

- Easier: no Node, no build step, fewer ways for the README run to fail.
- Harder: no component library, and we write the CSS ourselves. Pages reload on every action.
- If the UI grows a lot later, we can add Vite without touching the controllers.
