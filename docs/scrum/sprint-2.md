# Sprint 2: bookings that follow the rules

**Window:** Mon 2 Nov 2026, 07:30 to 16:00 (UTC)
**Goal:** A booking that breaks a rule is rejected with a message a person understands, and the project lead can see how much of each order's budget is used.

## Sprint backlog

| Item | Tasks | Status |
|---|---|---|
| PBI-05 Booking rules | One domain class that books time and checks the rules; the controller only calls it. Rules: not more than the remaining budget, not more than 10 hours per person per day, day inside the order period (the "order must be assigned" check already exists). Unit tests for every rule, accept and reject, plus one feature test per rule | done (31 tests green) |
| PBI-06 Budget per order | Used, remaining and ordered person-days per order; a gauge bar; a page for the project lead, purchasing and finance | done |
| PBI-07 Permissions | Who may open the order pages, checked on the server, with 403 tests (consultants see no order budgets, as in the role matrix) | done |

Planned order of work: PBI-05 first, because PBI-06 reads the same "used budget" the rules use.

## Design notes

- **Where the rules live:** `app/Domain`, not in the controller. A rule that is broken throws a domain exception whose message is shown to the user, for example "Booking rejected: only 2.5 PD left on order 4500012345."
- **Used budget** is the sum of hours of all bookings that are not rejected, divided by 8 (see [domain.md](../domain.md)).
- **Daily hour limit:** 10 hours per person per day across all orders, from the German Working Time Act. The 11-hour rest rule is not enforced, because we do not store start and end times (decision in the [product vision](../product-vision.md)).

## Daily Scrum log

**Mon 2 Nov, 07:50** PBI-05 is done. `TimeBooking` in `app/Domain` holds the four rules, the form only calls it and shows its message. 13 unit tests and 3 form tests added, `php artisan test` shows 31 passed. Next: PBI-06, the budget per order with a gauge bar.

**Mon 2 Nov, 08:05** PBI-06 and PBI-07 are done. `OrderBudget` in `app/Domain` works out ordered, used and remaining PD and the green, amber, red level. The `/orders` page is open to project lead, purchasing and finance only; a consultant gets a 403. `php artisan test` shows 44 passed. Still to do: look at the page in the browser, then the Sprint 2 review and retrospective.

## Review and retrospective

### Review (Mon 2 Nov, 08:10)

Shown to the Product Owner (Mohammed) in the browser as the project lead:

- A booking that breaks a rule is rejected with a plain message (budget, 10 hours a day, order period, assignment).
- The orders page shows ordered, used and left person-days per order with a green, amber or red bar. Customer portal 60% (green), data warehouse 80% (amber), mobile app relaunch 16.7% (green).
- A consultant gets a 403 on the orders page, a guest goes to the login page.

Sprint goal reached. `php artisan test`: 44 passed.

Feedback: the data warehouse order is amber, not red, because the bar only looks at budget used. Whether it is really in trouble depends on how fast the budget burns, and that is the forecast in Sprint 3 (PBI-08).

### Retrospective

- **Went well:** all rules sit in one class (`TimeBooking`), so the form, the tests and later the API can share them. `OrderBudget` takes plain numbers, which kept its tests fast. Small commits, each pushed right away.
- **Could be better:** I could not run PHP tests in the sandbox, so every step waited for a local run. Keep steps small so a failure is easy to find.
- **Next sprint:** the forecast needs the burn rate, absences and holidays. Build the date logic as plain functions first, test it hard, and only then put it on a page.
