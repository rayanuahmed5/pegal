# Pegal

Pegal is a time booking and budget forecast web app: external consultants book hours on a client's purchase orders, and the client's project lead sees used and remaining budget, a traffic light and the latest date to place a follow-up order. See the [product vision](docs/product-vision.md) and the [Scrum plan](docs/scrum/README.md).

> **Status (Mon 2 Nov 2026):** Sprint 1 is done: demo sign-in for four roles, consultant booking, demo data and tests. Next up: booking rules, the budget gauge and the forecast.

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

## Test

```bash
php artisan test
```

The tests use their own database, `pegal_test`, on the same MySQL server. It is created automatically, and your demo data is never touched.

## Demo users

All demo users have the password `pegal-demo`. They exist for the demo only.

| Role | Sign in with | What to try |
|---|---|---|
| Consultant | `consultant@pegal.test` | Open **Book time**, book hours on an assigned order, see them under **My bookings** |
| Project lead | `lead@pegal.test` | Sign in and open `/bookings`: the server answers 403, this role has no booking page |
| Purchasing | `purchasing@pegal.test` | Same: sign-in works, booking pages are closed to this role |
| Finance | `finance@pegal.test` | Same: sign-in works, booking pages are closed to this role |

The budget views for the client roles come in Sprint 2 and 3.

## Evidence map

Where the technical jury finds the evidence for each [technical criterion](docs/scoring.md).

| Criterion | Evidence |
|---|---|
| 1 · Persistence and data model | [Migrations](database/migrations) (suppliers, users with role, orders, assignments, bookings, absences, public holidays), [ER diagram](docs/domain.md), [seeders](database/seeders) |
| 2 · Business rules and unit tests | Rules (assignment, order period, 10 h per person and day, remaining budget) in [app/Domain/TimeBooking.php](app/Domain/TimeBooking.php), unit tests in [tests/Unit/TimeBookingTest.php](tests/Unit/TimeBookingTest.php), form-level tests in [tests/Feature/BookingTest.php](tests/Feature/BookingTest.php) |
| 3 · Roles, permissions and privacy | [Permission concept](docs/roles-and-privacy.md), enforced by [EnsureUserHasRole](app/Http/Middleware/EnsureUserHasRole.php) and [routes/web.php](routes/web.php), 403 tests in [BookingTest](tests/Feature/BookingTest.php) |
| 4 · Architecture and engineering | A structure: `app/Http`, `app/Domain`, `app/Models` · B [ADRs](docs/adr/) 0001 to 0004 · C Git history (many small commits since Mon 2 Nov) · D API docs: planned · E compose file: planned · F [mockup](docs/mockup): planned |

## Team

Mohammed ([@rayanuahmed5](https://github.com/rayanuahmed5)), with Claude as AI pair programmer. The process is Scrum, see [docs/scrum](docs/scrum/README.md).
