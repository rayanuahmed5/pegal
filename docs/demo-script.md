# Demo script (5 minutes) and answers for the jury

For the whole team. The order follows the challenge: record the work, track budgets, look ahead, check and approve, follow the rules. Every screen shown here is a real page of the product.

## Before you start (10 minutes earlier)

1. `git pull`, then `php artisan migrate:fresh --seed` (fresh demo data), then `php artisan serve`.
2. Open one private browser window per role, all on http://127.0.0.1:8000/login, password `pegal-demo`:
   `consultant@pegal.test`, `lead@pegal.test`, `purchasing@pegal.test`, `finance@pegal.test`, `admin@pegal.test`.
3. In the consultant window open **Book time**. In the others open the page named below.
4. Keep `docs/mockup/index.html` open in a tab as a fallback if the app fails.
5. The demo data is written for Monday 2 Nov 2026. On another day the forecast dates move by a few days, so **read the numbers from the screen**, do not recite them from this script.

## The five minutes

| Time | Who | Show | Say |
|---|---|---|---|
| 0:00 | Product Owner | Slide or spoken | The client buys consulting work from many suppliers and nobody knows how long a budget lasts. We stand on the **client's side**: project lead, purchasing, finance. The consultant books through a portal. We leave out on purpose: invoices, framework agreements, sick leave, AI. |
| 0:30 | Developer 1 | Consultant window, **Book time** | One booking: order, day, hours, description. Then break a rule: 11 hours in a day. The server answers with a plain sentence, nothing is saved. Try a day inside Ama's planned absence (21 Dec to 1 Jan). Optional start and end times: an overlap or less than 11 hours of rest is refused too. |
| 1:30 | Developer 2 | Project lead window, **Orders and budget** | Ordered, used, left in person-days. The traffic light is about the **date**, not only the percentage. Open the forecast: expected end date, reorder-by date with calendar week, the **fast and slow pace** (honest uncertainty, from the busiest and quietest of the last 4 weeks), and the assumptions behind every number. Holidays and vacations are in the calculation. |
| 2:30 | Developer 2 | Purchasing window, **Follow-up orders**, click **Draft** | Most urgent order first. The draft says how many PD to order, from which week, and by when. Copy the text into an e-mail. No consultant name appears. |
| 3:15 | Developer 3 | Finance window, **Proof of work** | Monthly sums per person and order, no days, no descriptions, no times. Reject one month **with a reason**. Switch to the consultant window, **My bookings**: the consultant sees who rejected and why. Rejected hours give the budget back. |
| 4:00 | Scrum Master | Admin window | Users in total, users per role, assigned and spent PD per person. Add a user and sign in with it. The administrator sees no orders and no bookings. |
| 4:30 | Scrum Master | Repository | Nine ADRs (stack, sign-in, operation, privacy of times, forecast), `php artisan test` with all rules covered by accept and reject tests, the Scrum board with issues, sprints and who did what. Close with the one sentence below. |

**Closing sentence:** *"Every number you saw is traceable: the forecast shows its assumptions and a range, the rules run on the server, and each role sees only what it needs."*

## What to say about the decisions the brief asks for

| Brief says | Our answer |
|---|---|
| Control vs. not becoming the employer | The client sees person-days per order, never start or end times. Times are optional and visible only to the consultant ([ADR 0008](adr/0008-optional-start-and-end-times.md)). |
| Transparency vs. privacy | Finance sees monthly sums per person. The project lead and purchasing see order-level numbers only. See [roles-and-privacy.md](roles-and-privacy.md). |
| Precision vs. effort | Hours per day is the default. Times are an option for people who want the overlap and rest checks. |
| One system vs. two | One portal where the consultant books. An import from supplier time tracking is a next step. |
| Clear forecast vs. honest uncertainty | One most likely date plus a fast and a slow case, and the assumptions on the page ([ADR 0005](adr/0005-forecast-burn-rate-and-working-calendar.md)). |

## Questions the jury may ask

| Question | Short answer | Where to look |
|---|---|---|
| Where is the booking rule code? | One class, `TimeBooking`, in the domain. Controllers only pass on its message. | `app/Domain/TimeBooking.php`, tests in `tests/Unit/TimeBookingTest.php` |
| How is the forecast calculated? | Average of the last 4 weeks, spread over 5 working days, scaled by how many consultants are available that day, walked forward day by day until the budget is zero. | `app/Domain/BudgetForecast.php`, `BurnRate.php`, `WorkCalendar.php` |
| Who may see what, and is it enforced? | Role matrix in the docs; the `role` middleware returns 403 on the server; feature tests prove it. | `routes/web.php`, `app/Http/Middleware/EnsureUserHasRole.php` |
| What if two people book at the same moment? | The booking runs in a transaction with locked rows, so the last hours of budget or of the day can't be taken twice. | `TimeBooking::book` |
| Why no Docker? | Decision: the team hosts with Laragon. A compose file nobody runs is a risk ([ADR 0007](adr/0007-operation-php-and-mysql-without-containers.md)). | `docs/adr` |
| How would this scale or be operated for a real customer? | Stateless PHP behind any web server, MySQL for data; sign-in would move to the customer's single sign-on ([ADR 0002](adr/0002-identity-demo-login-sso-later.md)). | `docs/adr` |
| Is there an API? | Read-only JSON for orders and follow-ups, same session and role checks, described in OpenAPI. | `public/openapi.yaml`, `/api-docs.html` |
| What did you leave out? | Invoices check, framework agreements, sick leave, break rules, holidays per federal state, scenarios, AI features. All are in the backlog as next steps. | `docs/scrum/product-backlog.md` |

## Every team member should be able to explain

- **Product Owner:** the problem, the three client roles, why the client's side, what was left out, the backlog order.
- **Scrum Master:** the sprints and events, the board, the Definition of Done, what the retrospectives changed.
- **Developers:** one rule in `app/Domain`, one test file, one migration, one page each. Open it before the pitch and read it aloud once.

## If something goes wrong

- A page shows an error: go back to the fallback mockup in `docs/mockup/index.html` and say it is the same flow.
- A number differs from the script: read the screen, it is calculated from today's date.
- The database is broken: `php artisan migrate:fresh --seed` brings the demo data back in about a minute.
