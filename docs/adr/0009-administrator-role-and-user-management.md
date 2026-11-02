# 0009 · Administrator role that manages accounts

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** development team (Mohammed Raiyan Ahmed)

## Context

Users only existed through the seeder. The team wants an administrator who can see everybody, their role, what they are assigned and what they have booked, and who can add new users. Pegal has no prices, only person-days (PD), so "amount" has to be PD.

## Options considered

1. **A flag on a work role** (for example "project lead who may also add users"): no new role, but permissions get mixed and the role matrix gets hard to read.
2. **A separate `admin` role (chosen):** one more value in the `Role` enum, one more demo user, its own routes behind `role:admin`.
3. **Add users only with an artisan command:** nothing to build in the browser, but the administrator would need a terminal.

## Decision

We choose **option 2**. The administrator opens `/admin` (users in total, users per role, and per person: assigned orders, assigned PD, spent PD) and `/admin/users/create`. The rule "a consultant belongs to a supplier, everybody else has none" lives in `App\Domain\UserAccounts`; the numbers come from `App\Domain\UserOverview`. Routes use the existing `role` middleware, so everybody else gets a 403.

The administrator is **only for accounts**: no orders, no forecast, no proof of work, no bookings, no descriptions and no times. The role matrix in `docs/roles-and-privacy.md` says so.

## Consequences

- Easier: the demo can show a user being added and signing in; the role matrix stays clean.
- Harder / risk: the administrator sees per-person spent PD, which is more than the client roles see. It is aggregated hours only, and that is why the role is kept off all work pages.
- "Assigned PD" is the sum of the budgets of the orders a person works on. Orders are shared, so the column must not be added up across people. The page says so.
- Passwords are chosen by the administrator and hashed; there is no password reset or e-mail invitation yet. For real use that would come with SSO ([ADR 0002](0002-identity-demo-login-sso-later.md)).
- Users cannot be edited or deleted yet, only added.
