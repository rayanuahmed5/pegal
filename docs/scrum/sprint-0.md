# Sprint 0: setup and planning

**Window:** Mon 2 Nov 2026, 01:30 to 03:00 (UTC)
**Goal:** Everything needed to start building: shared understanding, backlog and a working environment.

## Sprint backlog

| Item | Status |
|---|---|
| Read challenge, guide, scoring | done |
| Product vision ([product-vision.md](../product-vision.md)) | done |
| Domain model and ER diagram ([domain.md](../domain.md)) | done |
| Roles and privacy concept ([roles-and-privacy.md](../roles-and-privacy.md)) | done |
| ADR 0001 stack, 0002 identity, 0003 operation | done |
| Product backlog, Definition of Done, sprint plan | done |
| MySQL 8 installed, databases `pegal` and `pegal_test` created | done |
| Laravel project scaffolded | done in Sprint 1 (scaffolded on Mohammed's machine; Packagist is blocked in Claude's sandbox) |

## Daily Scrum log (Mon 2 Nov)

- **Done:** planning documents, MySQL ready.
- **Next:** scaffold Laravel, start Sprint 1.
- **Blocker:** the workspace's network policy rejects `repo.packagist.org` (HTTP 403 on CONNECT), so `composer create-project laravel/laravel` cannot download Laravel. Package registries for npm and pip work; Packagist is not on the allowed list.

## Sprint Review

Increment: complete planning documentation and a running MySQL. No application code yet. The team needs to decide how to unblock Laravel (see [Sprint 1](sprint-1.md)).

## Retrospective

- **Keep:** writing ADRs and the permission concept before code.
- **Change:** check that every download host is reachable (Packagist, GitHub) in the first ten minutes of the next project.
