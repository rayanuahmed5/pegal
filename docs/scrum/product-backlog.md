# Product backlog

Ordered by value. Estimates are story points (1, 2, 3, 5, 8). Status: `todo`, `doing`, `done`.

| ID | As a ... I want ... so that ... | Acceptance criteria | Pts | Sprint | Status |
|---|---|---|---|---|---|
| PBI-01 | developer, a Laravel + MySQL project that starts from the README | Fresh clone: install, migrate, seed, run and test commands work; `.env.example` has safe defaults | 3 | 1 | done |
| PBI-02 | team, a data model with seed data | Migrations for the 7 tables in [domain.md](../domain.md); seeders create 2 suppliers, 3 orders, 4 consultants and bookings; data survives restart | 3 | 1 | done |
| PBI-03 | user, to sign in as a demo user per role | Four demo users (consultant, project lead, purchasing, finance) can sign in; passwords in README | 2 | 1 | done |
| PBI-04 | consultant, to book date, hours and description on an assigned order | Form and list of own bookings; stored in MySQL; hours in 0.25 steps | 3 | 1 | done |
| PBI-05 | client, bookings that follow the rules | Rejected with a clear message when: over the remaining budget; more than 10 h per person per day; date outside the order period; order not assigned. Accept and reject tests for each | 8 | 2 | todo |
| PBI-06 | project lead, used and remaining budget per order | Order page shows ordered, used and remaining PD as a gauge bar; the traffic light comes with the forecast (PBI-08) | 3 | 2 | todo |
| PBI-07 | team, permissions enforced on the server | Policies per role as in the matrix; wrong role gets 403; feature tests | 5 | 2 | todo |
| PBI-08 | project lead, a forecast of when the budget runs out | Burn rate over 4 weeks, exhaustion date adjusted for absences and holidays, latest reorder date, assumptions shown; unit tests | 8 | 3 | todo |
| PBI-09 | purchasing, a list of orders needing a follow-up order | Orders whose reorder date is within 6 weeks, sorted by urgency | 3 | 3 | todo |
| PBI-10 | finance, monthly proof of work per order | Monthly hours per person per order without times; approve or reject | 5 | 3 | todo |
| PBI-11 | jury, a clickable mockup of the main screens per role | HTML files in `docs/mockup/`, linked, committed | 3 | 3 | todo |
| PBI-12 | jury, API documentation | Machine-readable OpenAPI file for the JSON endpoints, viewable in the browser | 3 | 3 | todo |
| PBI-13 | jury, `compose.yaml` that starts MySQL and the app | `docker compose up` gives a working app with seed data | 3 | 3 | todo |
| PBI-14 | jury, a README that works in 5 minutes | Six sections complete, evidence map links valid, a person outside the setup ran it | 3 | 3 | todo |
| PBI-15 | project lead, what-if scenario (extra consultant, longer project) | Forecast recalculated with changed inputs | 5 | stretch | todo |
| PBI-16 | purchasing, a prepared follow-up order proposal | Draft with suggested PD and start week | 3 | stretch | todo |

Done in Sprint 0: vision, domain model, roles and privacy concept, ADRs 0001 to 0003, Scrum setup.
