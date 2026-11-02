# Sprint 3: forecast, follow-up list, proof of work and the hand-in

**Window:** Mon 2 Nov 2026, 08:10 (started early, Sprint 2 finished ahead of plan) to Tue 3 Nov, 08:00. Tue 08:00 to 11:00 is the freeze: no new features, only the fresh-clone run check and fixes.
**Goal:** The project lead and purchasing see early when a budget runs out, finance can approve proof of work, and the jury can start and read everything from the README.

## Sprint backlog

| Item | Tasks | Status |
|---|---|---|
| PBI-08 Forecast | Burn rate, working calendar, exhaustion and reorder date, traffic light, shown on the orders page with assumptions; unit tests | done (75 tests green) |
| PBI-09 Follow-up list | Orders whose reorder date is within 6 weeks, most urgent first, for purchasing (all orders) and project lead | done |
| PBI-10 Proof of work | Finance sees monthly hours per person per order without times and approves or rejects | done |
| PBI-11 Mockup | Clickable HTML screens per role in `docs/mockup/` | done |
| PBI-12 API docs | Two JSON endpoints (`/api/orders`, `/api/follow-ups`) with a resource class as DTO, OpenAPI file, Swagger UI page | done |
| PBI-13 Compose | `compose.yaml` with MySQL and the app | dropped, the team is not using Docker (ADR 0007) |
| PBI-14 README run check | Fresh clone, the six README sections, evidence map links | done |

Planned order: PBI-09 and PBI-10 (they finish the roles), then PBI-14 early so a fresh-clone check can happen twice, then PBI-13, PBI-12 and PBI-11. Stretch items PBI-15 and PBI-16 only if time is left.

## Design notes

- **Forecast model:** weekly burn rate (last 28 days) is spread over five working days and scaled by the share of assigned consultants who are not on vacation that day. Weekends and public holidays burn nothing. Written down in [domain.md](../domain.md) and shown as "Assumptions" next to every forecast.
- **Traffic light:** red when the reorder date has passed or less than 10 % of the budget is left; amber when the budget ends before the order ends but there is still time; green otherwise.
- **Privacy:** the forecast is only calculated for project lead and purchasing, never for finance, and no person is shown.

## Daily Scrum log

**Mon 2 Nov, 08:20** PBI-08 is done: `WorkCalendar`, `BurnRate`, `BudgetForecast` and `ForecastResult` in `app/Domain`, forecast column on `/orders`. `php artisan test` shows 75 passed. Next: PBI-09, the follow-up list.

**Mon 2 Nov, 08:25** PBI-08 checked in the browser as the project lead. Customer portal: burn 6 PD per week, runs out 18 Jan 2027, reorder by 21 Dec 2026 (amber). Data warehouse: runs out 13 Nov 2026, reorder date 16 Oct has passed (red). Mobile app relaunch: runs out 23 Mar 2027, reorder by 9 Feb 2027 (amber). The numbers match a hand calculation: warehouse burns 4 PD per week with 8 PD left, which is ten working days.

**Mon 2 Nov, 09:05** PBI-09, PBI-10 and PBI-12 are done and green on the local machine (feature tests for the follow-up list, proof of work and the JSON API all pass). Impediment on the way: a test helper named `status()` clashed with a final PHPUnit method and stopped the whole test run; renamed, noted for the retrospective. PBI-13 (compose) is built but not yet run with Docker. Next: PBI-11 mockup, then PBI-14 README run check.

**Mon 2 Nov, 09:25** PBI-14 done: a fresh clone in a new folder, followed from the README only, installed, migrated, seeded and ran. First `hackathon-review` against the scoring: all building blocks present; the one gap is the Git history, which only has commits from Monday (see the retrospective).

**Mon 2 Nov, 09:30** Team decision: the team is not using Docker. PBI-13 is dropped, the compose file, Dockerfile and start script are removed, and ADR 0007 replaces ADR 0003 for the "operation" topic. The product still delivers the other building blocks (structure, ADRs, API docs, mockup, Git history).

**Mon 2 Nov, 12:30** Comparison with the Pegel build (the other team repository). Picked four ideas and added them as separate small commits: the planned-absence booking rule (PBI-17), missing PD until the order ends and ISO weeks in the forecast, page and API (PBI-18), a required reason and the decider on a rejection (PBI-19), and optional start and end times with the overlap and 11-hour rest rules (PBI-20, [ADR 0008](../adr/0008-optional-start-and-end-times.md)). The test workflow for GitHub Actions came from the same comparison. Two new migrations, so existing databases need `php artisan migrate`.

**Mon 2 Nov, 13:00** Team request: an administrator with a dashboard (users in total, role of every user, assigned and spent amount per user) who can add users. Pegal has no prices, so the amounts are person-days. Built as PBI-21: `Role::Admin`, demo user `admin@pegal.test`, `UserOverview` and `UserAccounts` in the domain with unit tests, `/admin` and `/admin/users/create` behind `role:admin`, feature tests, [ADR 0009](../adr/0009-administrator-role-and-user-management.md). The seeder only runs on an empty database, so an existing local database needs `php artisan migrate:fresh --seed` to get the admin user.

**Mon 2 Nov, 12:30** Second fresh-clone run check (#19): Mohammed cloned the repository again on his machine, followed the README and the app ran.

## Review and retrospective

### Review (Mon 2 Nov, 09:40)

Shown by Mohammed (developer) on his machine with Laragon, for the Product Owner (Abdulai Umar Faruk) to accept:

- **Project lead and purchasing:** orders with budget gauge and forecast (traffic light, exhaustion date, reorder date, assumptions), and the follow-up list, most urgent first (data warehouse order overdue).
- **Finance:** monthly proof of work per person and order, approve or reject; orders without forecast.
- **JSON API** (`/api/orders`, `/api/follow-ups`) with the same permissions, described in OpenAPI and viewable at `/api-docs.html`.
- **Clickable mockup** in `docs/mockup`, one set of screens per role.
- **README run check:** a fresh clone followed from the README alone started and a demo user could sign in.
- **Dropped:** PBI-13 (compose file), the team is not using Docker.

`php artisan test`: 122 passed (313 assertions). Sprint goal reached.

### Retrospective

- **Went well:** the pure domain classes (`WorkCalendar`, `BudgetForecast`, `FollowUp`) were tested with exact dates before any page existed, and then the pages and the API reused them through `OrderOverview`. Small commits, each pushed.
- **Could be better:** a test helper named `status()` clashed with a final PHPUnit method and stopped the whole run; and the Git history only starts on Monday, so the history will cover two hackathon days instead of five.
- **Decision to keep:** no Docker (ADR 0007). A compose file nobody runs is a risk, so it was removed.
- **Next (freeze, Tue 3 Nov 08:00 to 11:00):** no new features. A fresh-clone run check, a final review against the scoring, and a few small fixes at most.
