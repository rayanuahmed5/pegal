# 0010 · Editing and deleting accounts, keeping bookings

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** development team (Mohammed Raiyan Ahmed)

## Context

[ADR 0009](0009-administrator-role-and-user-management.md) let the administrator add users, nothing more. Now every user needs to see their profile and change their password, and the administrator needs to edit accounts, reset passwords and delete accounts. Deleting is the hard part: a consultant's bookings are the proof of work behind the supplier's invoices and must be kept for years (see the retention table in [roles-and-privacy.md](../roles-and-privacy.md)). The `bookings.user_id` foreign key already says `restrictOnDelete`.

## Options considered

1. **Delete everything (cascade):** simple, but it wipes proof of work and changes the used budget of past orders. Not acceptable.
2. **Soft delete (a `deleted_at` column):** the account disappears but stays in the database. Bookings stay intact, but every query has to remember to hide deleted users, and "deleted" personal data is still there.
3. **Anonymise:** replace name and email with a placeholder, keep the bookings. Good for privacy, but finance can no longer match hours to a person, and it needs a new process we have no time to test.
4. **Delete only accounts without bookings (chosen):** a real delete when nothing depends on the user; refuse with a clear message otherwise.

## Decision

We choose **option 4**. The rules live in `App\Domain\UserAccounts`, are enforced on the server and have unit tests for both cases:

- A user with bookings cannot be deleted. Assignments and planned absences go with a deleted account.
- An administrator cannot delete their own account or change their own role, so there is always an administrator left.
- A consultant with bookings keeps the role Consultant.
- A consultant must have a supplier (same rule as when adding).
- Changing your own password needs the current one; an administrator's reset does not.

## Consequences

- Easier: no change to the schema, proof of work can never be lost by accident, and the demo shows a rule being refused.
- Harder: a consultant who leaves keeps an account that can still sign in. The workaround is a password reset by the administrator. A proper fix is a "deactivated" flag or anonymisation after the retention period; both are open backlog items.
- A password reset does not sign the person out of a session that is already open. With SSO ([ADR 0002](0002-identity-demo-login-sso-later.md)) passwords leave Pegal anyway.
