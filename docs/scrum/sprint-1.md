# Sprint 1: walking skeleton

**Window:** Mon 2 Nov 2026, 03:00 to 12:00 (UTC)
**Goal:** A consultant signs in, books time on an assigned order, and the booking is stored in MySQL and survives a restart.

## Sprint backlog

| Item | Tasks | Status |
|---|---|---|
| PBI-01 Project setup | Scaffold Laravel 13, configure MySQL in `.env.example`, README Prerequisites/Setup/Run/Test | scaffold done (Laravel 13), MySQL config done, README commands next |
| PBI-02 Data model and seeds | 7 migrations, models, seeders | todo |
| PBI-03 Demo login | Seed four demo users, login page | todo |
| PBI-04 Book time | Booking form, own bookings list, one feature test | todo |

## Dependency

All items need the Laravel dependencies from Packagist. To unblock, the Product Owner can allow the host `repo.packagist.org` for this session (Settings > Capabilities on claude.ai; package managers can be allowed on the Free plan), or run the scaffold locally with `composer create-project laravel/laravel` and push it to the team repository.

## Daily Scrum log

**Mon 2 Nov**
- Done: Laravel 13 scaffold (run on Mohammed's machine), MySQL config, 7 migrations (ran clean on MySQL), role and status enums, models, seeders with one demo user per role.
- Next: check the seed data on MySQL, demo login, booking form.
- Blocker: none. Packagist stays blocked in Claude's sandbox, so every step is run on Mohammed's machine and the output pasted back.

## Review and retrospective

(at the end of the sprint)
