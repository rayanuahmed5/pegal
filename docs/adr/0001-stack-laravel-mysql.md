# 0001 · Stack: Laravel with MySQL

- **Status:** accepted
- **Date:** 2026-11-02
- **Deciders:** development team (Mohammed Raiyan Ahmed)

## Context

We need a stack that gives us routing, validation, authentication, migrations, seeders and a test runner out of the box, so a small team can finish a working product in the remaining hackathon time. The technical jury must start the product from the README in 5 minutes without paid accounts.

## Options considered

1. **Laravel + MySQL** – batteries included (Eloquent, migrations, policies, PHPUnit). Needs a MySQL server; PHP and Composer must be installed.
2. **Laravel + SQLite** – zero setup for the jury, but the team asked for MySQL and a server database is closer to production.
3. **Next.js + PostgreSQL** (the `blueprint` branch) – one language, but not the team's chosen stack.

## Decision

We choose **Laravel 13 on PHP 8.4 with MySQL 8**, because the team set this stack for the hackathon and Laravel covers most needs without extra libraries. The jury needs PHP, Composer and a MySQL server; the README lists the exact commands.

## Consequences

- Easier: migrations and seeders give reproducible data; policies give server-side permissions; PHPUnit gives unit tests.
- Harder: more setup than SQLite; the jury needs PHP, Composer and a local MySQL.
- Decision on data granularity: we store hours per day, not start and end times, so the 11-hour rest rule of the Working Time Act is not enforced. This is documented in the product vision.
- Layers: `app/Http` (requests and resources), `app/Domain` (rules and forecast), `app/Models` (persistence).
