# 0008 · Optional start and end times on a booking

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

Until now a booking only held hours per day (see `docs/domain.md`). That kept personal data small, but two working time rules could not be checked: bookings of one person must not overlap, and the Working Time Act asks for 11 hours of rest between two working days. The Pegel build checks both, and the jury looks for working time rules.

## Options considered

1. **Keep hours only:** least data, no change. The rest rule stays unchecked.
2. **Start and end time required on every booking:** both rules always work, but every booking needs more clicks and consultants who only know "about 6 hours" are blocked.
3. **Start and end time optional (chosen):** rules run when times are given, hours-only booking still works.

## Decision

We choose **option 3**. `bookings` gets nullable `starts_at` and `ends_at` (time of day). When both are given, the hours are the difference and must be a multiple of 15 minutes. Two server-side rules use them:

- **No overlap:** a timed booking may not overlap another timed booking of the same person on the same day.
- **11 hours of rest:** between the last end of the day before and the first start of the next day there must be at least 11 hours, checked in both directions. A day can not run past midnight.

Bookings without times are not checked by these two rules, the 10-hour daily limit still covers them.

## Consequences

- Easier: two more real working time rules with accept and reject tests; the consultant can see a tidy day.
- Harder / risk: start and end times are personal data. They are shown **only to the consultant who booked them**. The proof of work for finance (monthly sums), the order pages, the forecast and the API never contain times, which keeps the line from `docs/roles-and-privacy.md` that client roles never see start or end times.
- Rules only protect consultants who enter times. We say so in the README instead of pretending otherwise.
- Needs a migration (`php artisan migrate`) on existing databases.
