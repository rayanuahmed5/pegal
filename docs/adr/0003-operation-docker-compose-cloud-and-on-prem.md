# 0003 · Operation: containers that run in the cloud or at the customer

- **Status:** superseded by [0007](0007-operation-php-and-mysql-without-containers.md) on 2 Nov 2026, the team decided not to use Docker
- **Date:** 2026-11-02
- **Deciders:** Mohammed (Product Owner), development team

## Context

Large clients differ: some accept a cloud service, others require the product inside their own network. The product may see thousands of bookings per month, so it must not be tied to one host. The jury needs it to run locally without a cloud account.

## Options considered

1. **Cloud only (managed platform)** – simple to operate, but excludes customers that need on-premises.
2. **On-premises only** – meets strict customers, but we would have to operate every installation.
3. **Containers (Docker Compose locally, any container platform in production)** – the same image runs in both worlds.

## Decision

We choose **option 3**: the application and MySQL run as containers. `compose.yaml` starts them for the demo; the same image can run on a cloud container service or on the customer's servers. Configuration comes only from environment variables (`.env.example`).

## Consequences

- Scaling: thousands of bookings per month is small for MySQL; booking writes are short transactions. Reads for the budget view use indexed sums on `bookings(order_id)`. If load grows, we add read replicas and cache the forecast per order.
- Harder: the customer's IT must run containers and back up MySQL; we must document upgrades.
- Not decided yet: multi-tenancy model (one database per customer vs. shared); to be decided before the first real customer.
