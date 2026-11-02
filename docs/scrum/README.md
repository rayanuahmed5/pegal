# Scrum in this hackathon

We use Scrum, compressed to fit the hackathon window (ends **Tuesday 3 Nov 2026, 11:00**). Times are UTC.

## Roles (proposed, to be confirmed by the team)

| Scrum role | Who |
|---|---|
| Product Owner | Mohammed: owns the [product backlog](product-backlog.md) and accepts work |
| Scrum Master | Mohammed (or a teammate): protects the process and removes blockers |
| Developers | Claude as AI pair-programmer together with the team members |

## Events

| Event | When | Output |
|---|---|---|
| Sprint Planning | Start of each sprint | Sprint goal and sprint backlog in `sprint-N.md` |
| Daily Scrum | Once per day, 15 minutes (or a written log in `sprint-N.md`) | What was done, what is next, blockers |
| Sprint Review | End of each sprint | Demo of the increment; Product Owner accepts or rejects items |
| Sprint Retrospective | After each review | One thing to keep, one to change |

## Sprints

| Sprint | Window | Goal |
|---|---|---|
| [Sprint 0](sprint-0.md) | Mon 2 Nov 01:30 to 03:00 | Everything needed to start: vision, backlog, domain, roles, ADRs, working environment |
| [Sprint 1](sprint-1.md) | Mon 2 Nov 03:00 to 07:15 (closed early, goal met) | Walking skeleton: a consultant can book time and the data is stored in MySQL |
| [Sprint 2](sprint-2.md) | Mon 2 Nov 07:30 to 08:10 (closed early, goal met) | Bookings follow the rules (with tests) and the project lead sees the budget per order |
| [Sprint 3](sprint-3.md) | Mon 2 Nov 08:10 (started early) to Tue 3 Nov 08:00 | Forecast, follow-up list, proof of work, mockup, API docs, README |
| Freeze | Tue 3 Nov 08:00 to 11:00 | Run check from a fresh clone, final review against scoring, no new features |

## Definition of Done (applies to every backlog item)

1. Works end to end and data survives a restart (MySQL).
2. Business rules live in `app/Domain`, are enforced on the server and give an understandable message.
3. Every rule has tests for acceptance and rejection; `php artisan test` is green.
4. Permissions follow [roles-and-privacy.md](../roles-and-privacy.md) and are tested.
5. README commands are updated in the same change; the evidence map is current.
6. Committed with a clear message (`feat|fix|docs|test: ...`) and pushed.
7. Anything that is a technical decision has an ADR.
