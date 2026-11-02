# Roles, permissions and data privacy

See [guide 2.4](guide.md#24-roles-and-privacy).

## Roles

| Role | Side | Description |
|---|---|---|
| Consultant | supplier | Books working time on the orders they are assigned to |
| Project lead | client | Owns the project; watches budget and forecast of their orders |
| Purchasing | client | Places follow-up orders; needs to know early when an order runs out |
| Finance / controlling | client | Checks proof of work against invoices and approves it |
| Administrator | neither | Looks after accounts: sees all users with role, assigned and spent PD, adds new users ([ADR 0009](adr/0009-administrator-role-and-user-management.md)) |

## Role matrix

`✔` = allowed, `–` = not allowed, `Σ` = only aggregated values

| Action or data | Consultant | Project lead | Purchasing | Finance |
|---|---|---|---|---|
| Book own working time on an assigned order | ✔ | – | – | – |
| See own bookings | ✔ | – | – | – |
| See single bookings of an order (with person) | – | Σ by week, no person | – | ✔ monthly per person, no times |
| See used / remaining budget per order | only assigned orders | ✔ | ✔ | ✔ |
| See forecast and reorder date | – | ✔ | ✔ | – |
| See list of orders that need a follow-up order | – | ✔ own orders | ✔ all | – |
| Open the draft of a follow-up order (scope, period, order-by date; order-level numbers only) | – | ✔ | ✔ | – |
| Approve or reject proof of work | – | – | – | ✔ |
| See vacation of a consultant | own only | Σ (days without a name) | – | – |
| Create or change orders and assignments | – | – | ✔ | – |

### Administrator

| Action or data | Administrator |
|---|---|
| See all users with name, e-mail, role and supplier | ✔ |
| See assigned PD and spent PD per person (sums only) | ✔ |
| Add a new user | ✔ |
| Orders, budget, forecast, follow-up list | – |
| Proof of work, single bookings, descriptions, start and end times | – |
| Book time | – |

## Personal data

| Data | Why we store it | Who needs it | How long we keep it |
|---|---|---|---|
| Name and e-mail of a user | Sign-in and proof of work | The user; finance for proof of work | While the supplier contract runs plus 6 months |
| Hours per consultant per day | Budget and proof of work | Consultant; finance (monthly sums per person) | Until the order is closed plus 10 years (tax rules) or as the client's policy says |
| Booking description | Shows what was done, for proof of work | Consultant; finance | Same as bookings |
| Planned vacation | Forecast only | Consultant; project lead only as anonymous sum | Until the vacation has passed plus 3 months |

Start and end times are optional (see [ADR 0008](adr/0008-optional-start-and-end-times.md)) and visible only to the consultant who booked them. We do not store health data or sick leave.

## Conflicts and how we resolve them

- **Transparency vs. privacy:** the project lead needs to know if the budget lasts, not what a person did each day. The project lead sees person-days per order per week. Individual daily hours and vacation stay hidden.
- **Control vs. employer status:** a client that sees and steers individual working times risks acting as the employer (a legal risk in Germany). Our client roles never see start or end times and never see a per-person daily view, except finance in monthly sums for invoice checks.
- **Project lead and "own orders":** the matrix says the project lead sees the follow-up list for their own orders. Orders have no owner field yet, so for now the project lead sees all orders, as purchasing does. Both only get order-level numbers. An owner column on orders is the planned fix.
- **Purchasing speed vs. data minimisation:** purchasing gets order-level numbers only.

## How it is enforced

Permissions are checked **on the server** with Laravel policies and middleware, following this matrix. A request from the wrong role returns HTTP 403 and is covered by feature tests. Hiding a button is never the only check. Demo users: one per role, seeded on first start. Real sign-in (SSO) is described in [ADR 0002](adr/0002-identity-demo-login-sso-later.md).
