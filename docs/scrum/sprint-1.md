# Sprint 1: walking skeleton

**Window:** Mon 2 Nov 2026, 03:00 to 07:15 (UTC), closed early because the goal was met
**Goal:** A consultant signs in, books time on an assigned order, and the booking is stored in MySQL and survives a restart.

## Sprint backlog

| Item | Tasks | Status |
|---|---|---|
| PBI-01 Project setup | Scaffold Laravel 13, configure MySQL in `.env.example`, README Prerequisites/Setup/Run/Test | done |
| PBI-02 Data model and seeds | 7 migrations, models, seeders | done |
| PBI-03 Demo login | Seed four demo users, login page | done |
| PBI-04 Book time | Booking form, own bookings list, one feature test | done |

## Dependency

All items need the Laravel dependencies from Packagist. To unblock, the scaffold is created locally with `composer create-project laravel/laravel` and pushed to the team repository.

## Daily Scrum log

**Mon 2 Nov**
- Done: Laravel 13 scaffold (run on Mohammed's machine), MySQL config, 7 migrations (ran clean on MySQL), role and status enums, models, seeders with one demo user per role.
- Next: check the seed data on MySQL, demo login, booking form.
- Blocker: none. Composer runs only on Mohammed's machine, so every step is run there and the output is shared with the team.

## Sprint Review (Mon 2 Nov, 07:15)

**Increment:** a consultant can sign in, book hours on an assigned order and see the bookings; the other three roles can sign in and are kept out of the booking pages by the server. Data lives in MySQL and comes back with `php artisan migrate:fresh --seed`. 15 tests pass.

**Checked by Mohammed** (developer) after running the app and `php artisan test` on his own machine: PBI-01, PBI-02, PBI-03 and PBI-04.

**Not done:** nothing from the sprint backlog. The booking rules (budget, daily hours, order period) were never in this sprint, they start Sprint 2.

## Sprint Retrospective

- **Keep:** writing the ADRs and the permission concept before code (done before this sprint).
- **Keep:** very small commits, every step run on Mohammed's machine with the output shared, tests written together with the feature.
- **Change:** removing the two example tests also removed the `tests/Unit` folder, and `phpunit.xml` still pointed at it, so `php artisan test` broke for one round. After deleting files, check the config that mentions them.
- **Constraint:** the code can only be run on Mohammed's machine. Every change costs one round trip, so we keep steps small.
