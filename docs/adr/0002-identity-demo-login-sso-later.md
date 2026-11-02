# 0002 · Identity: demo login now, single sign-on per customer later

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

The client is a large company and will want single sign-on (SSO) with its own identity provider. Every customer of Pegel may use a different provider. Consultants belong to the supplier and may have their own company identity. In the hackathon we have no identity provider and no time to set one up.

## Options considered

1. **Seeded demo users with password sign-in (Laravel session auth)** – works offline, one user per role, trivial for the jury. Not suitable for production.
2. **OpenID Connect / SAML per customer from the start** – realistic, but needs an identity provider, secrets and time we do not have.
3. **Magic-link e-mail sign-in** – needs a mail service.

## Decision

**Demo:** option 1, with seeded demo users, one per role, passwords listed in the README. **Production:** OpenID Connect, configured per customer (issuer, client id, secret stored per tenant); users are matched by e-mail and mapped to a Pegel role by group claim. The login is isolated behind Laravel's authentication guard, so replacing it does not touch the domain code.

## Consequences

- Easier: demo and tests need no external service.
- Harder: before production we must add per-tenant OIDC configuration and role mapping.
- Risk: demo passwords must never be used outside the demo; they are documented as such.
