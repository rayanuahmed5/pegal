# Product backlog

Ordered by value. Estimates are story points (1, 2, 3, 5, 8). Status: `todo`, `doing`, `done`.

| ID | As a ... I want ... so that ... | Acceptance criteria | Pts | Sprint | Status |
|---|---|---|---|---|---|
| PBI-01 | developer, a Laravel + MySQL project that starts from the README | Fresh clone: install, migrate, seed, run and test commands work; `.env.example` has safe defaults | 3 | 1 | done |
| PBI-02 | team, a data model with seed data | Migrations for the 7 tables in [domain.md](../domain.md); seeders create 2 suppliers, 3 orders, 4 consultants and bookings; data survives restart | 3 | 1 | done |
| PBI-03 | user, to sign in as a demo user per role | Four demo users (consultant, project lead, purchasing, finance) can sign in; passwords in README | 2 | 1 | done |
| PBI-04 | consultant, to book date, hours and description on an assigned order | Form and list of own bookings; stored in MySQL; hours in 0.25 steps | 3 | 1 | done |
| PBI-05 | client, bookings that follow the rules | Rejected with a clear message when: over the remaining budget; more than 10 h per person per day; date outside the order period; order not assigned. Accept and reject tests for each | 8 | 2 | done |
| PBI-06 | project lead, used and remaining budget per order | Order page shows ordered, used and remaining PD as a gauge bar; the traffic light comes with the forecast (PBI-08) | 3 | 2 | done |
| PBI-07 | team, permissions enforced on the server | Policies per role as in the matrix; wrong role gets 403; feature tests | 5 | 2 | done |
| PBI-08 | project lead, a forecast of when the budget runs out | Burn rate over 4 weeks, exhaustion date adjusted for absences and holidays, latest reorder date, assumptions shown; unit tests | 8 | 3 | done |
| PBI-09 | purchasing, a list of orders needing a follow-up order | Orders whose reorder date is within 6 weeks, sorted by urgency | 3 | 3 | done |
| PBI-10 | finance, monthly proof of work per order | Monthly hours per person per order without times; approve or reject | 5 | 3 | done |
| PBI-11 | jury, a clickable mockup of the main screens per role | HTML files in `docs/mockup/`, linked, committed | 3 | 3 | done |
| PBI-12 | jury, API documentation | Machine-readable OpenAPI file for the JSON endpoints, viewable in the browser | 3 | 3 | done |
| PBI-13 | jury, `compose.yaml` that starts MySQL and the app | `docker compose up` gives a working app with seed data. Dropped on Mon 2 Nov 09:30: the team is not using Docker (see ADR 0007) | 3 | 3 | dropped |
| PBI-14 | jury, a README that works in 5 minutes | Six sections complete, evidence map links valid, a person outside the setup ran it | 3 | 3 | done |
| PBI-15 | project lead, what-if scenario (extra consultant, longer project) | Forecast recalculated with changed inputs | 5 | stretch | todo |
| PBI-16 | purchasing, a prepared follow-up order proposal | Draft with suggested PD and start week | 3 | 3 | review |
| PBI-17 | consultant, no booking on a day of my planned absence | Server rejects it with the absence dates in the message; days around it are fine | 2 | 3 | done |
| PBI-18 | purchasing, how many PD are missing until the order ends, and the calendar weeks | Forecast shows missing PD and ISO weeks on the pages and in the API | 3 | 3 | done |
| PBI-19 | consultant, to know who rejected my hours and why | Finance must give a reason; consultant sees decider, date and reason under My bookings | 3 | 3 | done |
| PBI-20 | consultant, optional start and end times with overlap and 11-hour rest checks | ADR 0008; rules enforced on the server with accept and reject tests; times visible to the consultant only | 5 | 3 | done |
| PBI-21 | administrator, a dashboard with all users, their roles, assigned and spent PD, and a form to add users | ADR 0009; `/admin` for admins only (403 for others); account rule with accept and reject tests; demo user `admin@pegal.test` | 5 | 3 | done |

Prepared before Sprint 1: vision, domain model, roles and privacy concept, ADRs 0001 to 0003, Scrum setup (see [README](README.md#before-sprint-1-product-backlog-and-setup)).
Prepared after the crisis challenged was posed.
| ID | As a ... I want ... so that ... | Acceptance criteria | Pts | Sprint | Status |
|---|---|---|---:|---:|---|
| PBI-01 | As a developer, I want to disable the faulty calculation feature while preserving time-recording functionality so that incorrect payroll and invoice calculations are prevented without interrupting time capture. | Faulty calculation feature is disabled; consultants can still record time; users cannot trigger the faulty calculations; other application functions remain operational. | 3 | 4 | todo |
| PBI-02 | As a developer, I want to identify and reconstruct records affected by the calculation defect so that accurate employee and project data can be restored. | All affected records are identified; the affected period is established; original time-recording data is retrieved; affected records are corrected or reconstructed; corrected records are verified. | 8 | 4 | todo |
| PBI-03 | As a developer, I want to implement a reconciliation process for affected payroll figures and invoices so that discrepancies between original and corrected calculations can be identified and resolved. | Affected payroll and invoices can be identified; original and corrected figures can be compared; discrepancies are documented; necessary adjustments can be prepared; adjustments can be reviewed and approved by authorised personnel. | 5 | 4 | todo |
| PBI-04 | As a developer, I want to fix and thoroughly test the calculation defect before restoring the feature so that future payroll and invoice calculations produce accurate results. | Root cause of the defect is identified; correction is implemented; unit and integration tests pass; regression tests pass; corrected records produce expected results; feature is restored only after successful verification. | 8 | 4 | todo |
