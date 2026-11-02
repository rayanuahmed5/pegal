# Sprint 2: bookings that follow the rules

**Window:** Mon 2 Nov 2026, 07:30 to 16:00 (UTC)
**Goal:** A booking that breaks a rule is rejected with a message a person understands, and the project lead can see how much of each order's budget is used.

## Sprint backlog

| Item | Tasks | Status |
|---|---|---|
| PBI-05 Booking rules | One domain class that books time and checks the rules; the controller only calls it. Rules: not more than the remaining budget, not more than 10 hours per person per day, day inside the order period (the "order must be assigned" check already exists). Unit tests for every rule, accept and reject, plus one feature test per rule | todo |
| PBI-06 Budget per order | Used, remaining and ordered person-days per order; a gauge bar; a page for the project lead, purchasing and finance | todo |
| PBI-07 Permissions | Who may open the order pages, checked on the server, with 403 tests (consultants see no order budgets, as in the role matrix) | todo |

Planned order of work: PBI-05 first, because PBI-06 reads the same "used budget" the rules use.

## Design notes

- **Where the rules live:** `app/Domain`, not in the controller. A rule that is broken throws a domain exception whose message is shown to the user, for example "Booking rejected: only 2.5 PD left on order 4500012345."
- **Used budget** is the sum of hours of all bookings that are not rejected, divided by 8 (see [domain.md](../domain.md)).
- **Daily hour limit:** 10 hours per person per day across all orders, from the German Working Time Act. The 11-hour rest rule is not enforced, because we do not store start and end times (decision in the [product vision](../product-vision.md)).

## Daily Scrum log

(filled in each day)

## Review and retrospective

(at the end of the sprint)
