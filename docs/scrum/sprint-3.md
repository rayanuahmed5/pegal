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

**Mon 2 Nov, 09:30** Product Owner decision: the team is not using Docker. PBI-13 is dropped, the compose file, Dockerfile and start script are removed, and ADR 0007 replaces ADR 0003 for the "operation" topic. The product still delivers the other building blocks (structure, ADRs, API docs, mockup, Git history).

## Review and retrospective

(at the end of the sprint)
