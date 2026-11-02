# Pegal

Pegal is a time booking and budget forecast web app: external consultants book hours on a client's purchase orders, and the client's project lead sees used and remaining budget, a traffic light and the latest date to place a follow-up order. See the [product vision](docs/product-vision.md) and the [Scrum plan](docs/scrum/README.md).

> **Status (Mon 2 Nov 2026):** Sprints 1 and 2 are done. Sprint 3 is running: forecast with traffic light, follow-up list and proof of work are built. Still to come: mockup, API docs, compose file and the final run check.

## Prerequisites

| Tool | Version | Check |
|---|---|---|
| PHP (with the `pdo_mysql` extension) | 8.3 or newer | `php --version` |
| Composer | 2.x | `composer --version` |
| MySQL | 8.0, running on `127.0.0.1:3306` | `mysql --version` |

`.env.example` expects the MySQL user `root` with an empty password. If yours is different, change `DB_USERNAME` and `DB_PASSWORD` in `.env` after the copy step below.

## Setup

From a fresh clone, in the project folder:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

`migrate` offers to create the `pegal` database if it is missing, answer yes. You can also create it first: `CREATE DATABASE pegal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

To wipe the data and load the demo data again: `php artisan migrate:fresh --seed`

## Run

```bash
php artisan serve
```

Open http://127.0.0.1:8000 and sign in with one of the demo users below.

## Run with Docker

No PHP or MySQL on your machine? Docker is enough:

```bash
docker compose up --build
```

The first start builds the image, creates the tables and loads the demo data (later starts skip that). Open http://localhost:8000 and use the demo users below. The data is kept in a Docker volume; `docker compose down -v` wipes it.

## Test

```bash
php artisan test
```

The tests use their own database, `pegal_test`, on the same MySQL server. It is created automatically, and your demo data is never touched.

## Demo users

All demo users have the password `pegal-demo`. They exist for the demo only.

| Role | Sign in with | What to try |
|---|---|---|
| Consultant | `consultant@pegal.test` | **Book time** on an assigned order, see it under **My bookings**. Try 11 hours in one day, or more than the order has left: the server rejects it with a message. `/orders` answers 403 |
| Project lead | `lead@pegal.test` | **Orders and budget**: ordered, used and left person-days, a gauge, the forecast with reorder date and its assumptions. **Follow-up orders**: what needs a new order soon. `/bookings` answers 403 |
| Purchasing | `purchasing@pegal.test` | Same pages as the project lead: **Orders and budget** with forecast and **Follow-up orders**, most urgent first |
| Finance | `finance@pegal.test` | **Proof of work**: hours per person and order for a month, **Approve** or **Reject**. **Orders and budget** without forecast. `/follow-ups` answers 403 |

The demo data is written for today's date in the hackathon week (Mon 2 Nov 2026): the data warehouse order (4500012390) shows red, the other two orders amber.

## Evidence map

Where the technical jury finds the evidence for each [technical criterion](docs/scoring.md).

| Criterion | Evidence |
|---|---|
| 1 · Persistence and data model | [Migrations](database/migrations) (suppliers, users with role, orders, assignments, bookings, absences, public holidays), [ER diagram](docs/domain.md), [seeders](database/seeders) |
| 2 · Business rules and unit tests | Rules (assignment, order period, 10 h per person and day, remaining budget) in [app/Domain/TimeBooking.php](app/Domain/TimeBooking.php), unit tests in [tests/Unit/TimeBookingTest.php](tests/Unit/TimeBookingTest.php), form-level tests in [tests/Feature/BookingTest.php](tests/Feature/BookingTest.php). Forecast: [BudgetForecast](app/Domain/BudgetForecast.php), [BurnRate](app/Domain/BurnRate.php), [WorkCalendar](app/Domain/WorkCalendar.php), [FollowUp](app/Domain/FollowUp.php) with their unit tests in [tests/Unit](tests/Unit). Proof of work: [ProofOfWork](app/Domain/ProofOfWork.php) and [its tests](tests/Unit/ProofOfWorkTest.php) |
| 3 · Roles, permissions and privacy | [Permission concept](docs/roles-and-privacy.md), enforced on the server by [EnsureUserHasRole](app/Http/Middleware/EnsureUserHasRole.php) and [routes/web.php](routes/web.php), 403 tests in [BookingTest](tests/Feature/BookingTest.php), [OrderPageTest](tests/Feature/OrderPageTest.php), [FollowUpPageTest](tests/Feature/FollowUpPageTest.php) and [ProofOfWorkPageTest](tests/Feature/ProofOfWorkPageTest.php) |
| 4 · Architecture and engineering | A structure: `app/Http`, `app/Domain`, `app/Models` · B [ADRs](docs/adr/) 0001 to 0004 · C Git history (many small commits since Mon 2 Nov) · D API docs: planned · E [compose.yaml](compose.yaml) with [Dockerfile](Dockerfile) · F [mockup](docs/mockup): planned |

## Team

Mohammed ([@rayanuahmed5](https://github.com/rayanuahmed5)), with Claude as AI pair programmer. The process is Scrum, see [docs/scrum](docs/scrum/README.md).
