# 0005 · Forecast: weekly burn rate, spread over working days

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

The client wants to know early when an order's budget runs out, so that purchasing can place a follow-up order in time. The forecast must be explainable to a project lead and must take weekends, public holidays and planned vacations into account (see [domain.md](../domain.md)). We have no start and end times, only hours per day.

## Options considered

1. **Straight line:** remaining PD divided by the average PD per week gives weeks left. Simple, but a Christmas week or a vacation shifts the date by days that this ignores.
2. **Day by day walk (chosen):** take the burn rate of the last 4 weeks, spread it over five working days and walk forward from today. Weekends and public holidays burn nothing; on a day when part of the team is on vacation, only the available share burns.
3. **Capacity per person:** model each consultant's own pace. Most precise, but needs more data than we have and is hard to explain in a demo.

## Decision

We choose **option 2**, implemented as plain domain classes in `app/Domain` with no database access: `BurnRate` (PD per week over the 28 days before today), `WorkCalendar` (weekends, holidays, vacations), `BudgetForecast` (exhaustion date, reorder date, traffic light) and `FollowUp` (which orders need action within 6 weeks). The controller loads the rows and hands plain arrays to them. Every forecast shows its assumptions on the page.

## Consequences

- Easier: the classes are pure, so they are tested with exact dates (holiday in the way, one of two consultants away, borders of each colour). The date is explainable: "6 PD per week, 56 left, Christmas in between".
- Harder: the burn rate of the last 4 weeks already contains past holidays and vacations, which makes it slightly low for those weeks. We accept this and say so in the assumptions. A project that speeds up or slows down changes the forecast only slowly.
- A what-if scenario (extra consultant, longer project) can reuse `BudgetForecast` with other inputs (backlog item PBI-15).
