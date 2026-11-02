# Pegal

Pegal is a time booking and budget forecast web app: external consultants book hours on a client's purchase orders, and the client's project lead sees used and remaining budget, a traffic light and the latest date to place a follow-up order. See the [product vision](docs/product-vision.md) and the [Scrum plan](docs/scrum/README.md); the Scrum board is a [GitHub Project](https://github.com/users/rayanuahmed5/projects/3) over the repository's [issues](https://github.com/rayanuahmed5/pegal/issues) ([how it works](docs/scrum/board.md)).

> **Status (Mon 2 Nov 2026):** Sprints 1 to 3 are done: booking with rules, budget and forecast, follow-up list, proof of work, JSON API with OpenAPI docs and a clickable mockup. tests pass (run `php artisan test`). Left: the final checks before the Tuesday freeze.

## Prerequisites

| Tool | Version | Check |
|---|---|---|
| PHP (with the `pdo_mysql` extension) | 8.3 or newer | `php --version` |
| Composer | 2.x | `composer --version` |
| MySQL | 8.0, running on `127.0.0.1:3306` | `mysql --version` |

`.env.example` expects the MySQL user `root` with an empty password. If yours is different, change `DB_USERNAME` and `DB_PASSWORD` in `.env` after the copy step below.

**The team's own setup is [Laragon](https://laragon.org) on Windows** (PHP 8.3 or newer, Composer and MySQL 8 come with it; start its services before the setup steps). Laragon's MySQL uses `root` without a password, so `.env.example` works unchanged. No Docker is used (see [ADR 0007](docs/adr/0007-operation-php-and-mysql-without-containers.md)).

## Setup

From a fresh clone, in the project folder:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan pegal:create-database
php artisan migrate --seed
```

`pegal:create-database` creates the empty MySQL database `pegal` if it is missing (it never asks a question, and is harmless if the database exists). `migrate --seed` then creates the tables and the demo data.

To wipe the data and load the demo data again: `php artisan migrate:fresh --seed`

## Run

```bash
php artisan serve
```

Open http://127.0.0.1:8000 and sign in with one of the demo users below.

**With Laragon:** put the project folder in `C:\laragon\www\pegal`. Laragon can serve it by itself as a local site (auto virtual host), usually http://pegal.test, after you press *Start All* and reload the web server. If that address does not open, use `php artisan serve` as above, it always works.

## API

Two JSON endpoints show the same data as the pages: `GET /api/orders` and `GET /api/follow-ups`. They use the sign-in session and the same role checks. The OpenAPI file is [public/openapi.yaml](public/openapi.yaml); with the app running, open http://127.0.0.1:8000/api-docs.html (needs internet for the Swagger UI script), sign in first, then use "Try it out".

## Test

```bash
php artisan test
```

The tests use their own database, `pegal_test`, on the same MySQL server. It is created automatically, and your demo data is never touched.

## Demo users

All demo users have the password `pegal-demo`. They exist for the demo only.

| Role | Sign in with | What to try |
|---|---|---|
| Consultant | `consultant@pegal.test` | **Book time** on an assigned order, see it under **My bookings**. Try 11 hours in one day, a day inside a planned absence (Ama: 21 Dec to 1 Jan), two overlapping times, or more than the order has left: the server rejects it with a message. Start and end are optional, only you see them. `/orders` answers 403 |
| Project lead | `lead@pegal.test` | **Orders and budget**: ordered, used and left person-days, a gauge, the forecast with reorder date and its assumptions. **Follow-up orders**: what needs a new order soon. `/bookings` answers 403 |
| Purchasing | `purchasing@pegal.test` | Same pages as the project lead: **Orders and budget** with forecast and **Follow-up orders**, most urgent first |
| Finance | `finance@pegal.test` | **Proof of work**: hours per person and order for a month, **Approve** or **Reject** (a rejection needs a reason, the consultant reads it). **Orders and budget** without forecast. `/follow-ups` answers 403 |
| Administrator | `admin@pegal.test` | **Administration**: users in total and per role, and per person the assigned and spent person-days. **Add user** creates an account that can sign in right away. `/orders` answers 403 |

The demo data is written for today's date in the hackathon week (Mon 2 Nov 2026): the data warehouse order (4500012390) shows red, the other two orders amber.

## Evidence map

Where the technical jury finds the evidence for each [technical criterion](docs/scoring.md).

| Criterion | Evidence |
|---|---|
| 1 · Persistence and data model | [Migrations](database/migrations) (suppliers, users with role, orders, assignments, bookings, absences, public holidays), [ER diagram](docs/domain.md), [seeders](database/seeders) |
| 2 · Business rules and unit tests | Rules (assignment, order period, planned absence, overlap, 11 h rest, 10 h per person and day, remaining budget) in [app/Domain/TimeBooking.php](app/Domain/TimeBooking.php), unit tests in [tests/Unit/TimeBookingTest.php](tests/Unit/TimeBookingTest.php), form-level tests in [tests/Feature/BookingTest.php](tests/Feature/BookingTest.php). Forecast: [BudgetForecast](app/Domain/BudgetForecast.php), [BurnRate](app/Domain/BurnRate.php), [WorkCalendar](app/Domain/WorkCalendar.php), [FollowUp](app/Domain/FollowUp.php) with their unit tests in [tests/Unit](tests/Unit). Accounts: [UserAccounts](app/Domain/UserAccounts.php) and [UserOverview](app/Domain/UserOverview.php). Proof of work: [ProofOfWork](app/Domain/ProofOfWork.php) and [its tests](tests/Unit/ProofOfWorkTest.php) |
| 3 · Roles, permissions and privacy | [Permission concept](docs/roles-and-privacy.md), enforced on the server by [EnsureUserHasRole](app/Http/Middleware/EnsureUserHasRole.php) and [routes/web.php](routes/web.php), 403 tests in [BookingTest](tests/Feature/BookingTest.php), [OrderPageTest](tests/Feature/OrderPageTest.php), [FollowUpPageTest](tests/Feature/FollowUpPageTest.php) and [ProofOfWorkPageTest](tests/Feature/ProofOfWorkPageTest.php) |
| 4 · Architecture and engineering | A structure: `app/Http`, `app/Domain`, `app/Models` · B [ADRs](docs/adr/) 0001 to 0009 · C Git history (many small commits since Mon 2 Nov) · D API docs: [openapi.yaml](public/openapi.yaml), in the browser at `/api-docs.html` · E containers: not used, see [ADR 0007](docs/adr/0007-operation-php-and-mysql-without-containers.md) · F [clickable mockup](docs/mockup/README.md) (open `docs/mockup/index.html`) |

## Team

Mohammed ([@rayanuahmed5](https://github.com/rayanuahmed5)), with Claude as AI pair programmer. The process is Scrum, see [docs/scrum](docs/scrum/README.md).
