# Product vision

## Product name

**Pegel** (German for water-level gauge: a gauge that shows how much budget is left in each order).

## In one sentence

For **the client's project lead**, who **learns how much of an order's budget is used only when the monthly invoice arrives**, our product **Pegel** is a **time booking and budget forecast web application** that **shows every booked hour's effect on the order budget immediately and warns when a follow-up order must be placed**. Unlike spreadsheets and the old tool, it **forecasts when the budget runs out and the latest date to reorder**.

## Who we build for first

The **client's project lead**: the person who must know "will my budget last until the project ends?"

## Roles

| Role | Side (client / supplier) | What they want from the product |
|---|---|---|
| Project lead | client | See used and remaining budget per order, and a warning with a reorder date before the budget runs out |
| Purchasing | client | Know early which orders need a follow-up order (purchasing needs about 4 weeks) |
| Finance / controlling | client | Check monthly proof of work per order against the invoice without manual spreadsheet work |
| External consultant | supplier | Book working time on an assigned order in a few clicks, with a short description |

## The core flow

1. The consultant signs in and sees only the orders assigned to them.
2. The consultant books a date, hours and a short description against an order.
3. The server checks the rules (remaining budget, daily hour limit, order period) and rejects with an understandable message if one is broken.
4. The booking is stored; the order's used and remaining budget (in person-days) update.
5. The project lead opens the order and sees used / remaining budget, a traffic light, the forecast exhaustion date and the latest reorder date.

## What makes us special

- **The Pegel gauge:** one glance shows ordered, used and remaining budget plus a traffic light.
- **Honest forecast:** exhaustion date and latest reorder date, with the assumptions shown (average burn, absences, holidays, purchasing lead time).
- **Privacy by design:** the client sees person-days per order per week, not what an individual did each day.

## What we leave out on purpose

- Real single sign-on (documented in an ADR; the demo uses seeded demo users)
- Invoice import and automatic invoice-to-hours matching
- Framework agreements spanning several orders
- Exact start and end times (we store hours per day; the 11-hour rest rule therefore is not enforced)
- State-specific public holidays (only nationwide holidays are seeded)
- AI features

## Our track

**Working code**, with a clickable HTML mockup as fallback and as a building block (see [guide 1.3](guide.md#13-choose-your-track)).

## Our stack

**Laravel (PHP 8.4) with MySQL 8**, see [ADR 0001](adr/0001-stack-laravel-mysql.md). Scrum process: [docs/scrum](scrum/README.md).
