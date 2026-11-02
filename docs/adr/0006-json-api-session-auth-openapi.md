# 0006 · JSON API on the sign-in session, described with OpenAPI

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

The technical scoring asks for API documentation. Our pages are rendered on the server, so there was no JSON API at all. The API must not become a second way around the permission concept, and a jury member must be able to try it without setting up tokens or accounts.

## Options considered

1. **Token API with Laravel Sanctum:** the usual choice for outside clients, but it adds a package, a token table and a way to hand out tokens, all for a demo with demo users.
2. **JSON endpoints on the same session and role middleware as the pages (chosen):** nothing new to install; sign in once in the browser and the API works in the same browser.
3. **No API, only pages:** nothing to document, but the scoring item would be empty.

## Decision

We choose **option 2**. `GET /api/orders` and `GET /api/follow-ups` live in `routes/web.php` behind the `auth` and `role` middleware, use the same `OrderOverview` class as the pages, and return `OrderBudgetResource` objects as their data transfer shape. They are described in `public/openapi.yaml`, viewable at `/api-docs.html` (Swagger UI from a CDN).

## Consequences

- Easier: one set of permission rules and one set of data, the API can never show more than the pages. No new packages.
- Harder: a script outside the browser has to carry a session cookie; there are no tokens. For real integrations (SSO, see [ADR 0002](0002-identity-demo-login-sso-later.md)) we would add Sanctum or OAuth and keep the same resources.
- The API is read-only for now. Booking over JSON is not offered.
