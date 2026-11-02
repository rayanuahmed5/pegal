# 0007 · Operation: a plain PHP and MySQL application, no containers

- **Status:** accepted (replaces [0003](0003-operation-docker-compose-cloud-and-on-prem.md))
- **Date:** 2026-11-02
- **Deciders:** development team (Mohammed Raiyan Ahmed)

## Context

[ADR 0003](0003-operation-docker-compose-cloud-and-on-prem.md) chose containers (Docker Compose) so that one image runs in the cloud and at the customer. The team does not use Docker: nobody develops with it, and a compose file that nobody runs is a risk, not evidence. We still have to answer the same operation question: where does the product run, and how does it scale?

## Options considered

1. **Containers (Docker Compose)** – one image everywhere, but the team does not use Docker and could not test it.
2. **Plain PHP application with MySQL on a normal host (chosen)** – runs on a cloud VM, a PHP platform or the customer's own server. Needs PHP 8.3 with `pdo_mysql`, Composer and MySQL 8.
3. **Managed cloud platform only** – simple to operate, but excludes customers that need the product in their own network.

## Decision

We choose **option 2**. The team hosts it locally with **Laragon** on Windows (PHP, Composer, MySQL and a local web server in one install). Pegel is a standard Laravel application: `composer install`, set the environment, `php artisan migrate`, serve it with PHP's built-in server for the demo or with Nginx/Apache and PHP-FPM in production. All configuration comes from environment variables (`.env.example`), so the same code runs in the cloud and on the customer's servers. The README describes exactly these steps; `php artisan pegal:create-database` creates the empty database.

## Consequences

- Scaling: thousands of bookings per month is small for MySQL; booking writes are short transactions and the budget view reads indexed sums on `bookings(order_id)`. The application keeps no state on the server's disk (sessions and cache are in MySQL), so several app servers can run behind a load balancer. If load grows, we add MySQL read replicas and cache the forecast per order.
- Harder: the customer's IT installs PHP and MySQL, backs up MySQL and follows our upgrade notes (migrations). Containers would have made that more uniform.
- We can still add a Dockerfile later without changing the code, because the configuration is already environment based.
- Not decided yet: multi-tenancy model (one database per customer or a shared one), to be decided before the first real customer.
