# Scrum board

The board lives on GitHub, next to the code. One card is one issue, and the **column is the issue's `status:` label**. Sprints are **milestones**.

- Issues: https://github.com/rayanuahmed5/pegal/issues
- Sprints: https://github.com/rayanuahmed5/pegal/milestones
- Backlog items have the label `pbi`, sprint tasks have the label `task`.

## Columns

| Column | Label | Meaning |
|---|---|---|
| Backlog | `status: backlog` | In the product backlog, not planned for a sprint yet |
| To do | `status: to do` | Planned for the sprint, not started |
| Doing | `status: doing` | Being worked on right now |
| Review | `status: review` | Built, waiting for the Product Owner to check it against the acceptance criteria |
| Done | `status: done` (issue closed) | Meets the [Definition of Done](README.md#definition-of-done-applies-to-every-backlog-item) |
| Dropped | `status: dropped` (issue closed as not planned) | Taken out on purpose, with the reason in the issue |

Moving a card means changing the label (and closing the issue when it is Done). Only one `status:` label is on a card at a time.

## Board on Mon 2 Nov 2026, 12:45 UTC

| Backlog | To do | Doing | Review | Done | Dropped |
|---|---|---|---|---|---|
| #15 PBI-15 What-if scenario |  |  | #18 Click through Proof of work as finance | #1 to #12 PBI-01 to PBI-12 | #13 PBI-13 Compose file |
| #16 PBI-16 Follow-up order draft |  |  |  | #14 PBI-14 README run check |  |
|  | #21 Commits on Tuesday |  |  | #17 Create the Scrum board on GitHub, #19 Fresh-clone run check, #20 Final review against the scoring |  |
|  | #27 Add all issues to the GitHub Project (manual) |  |  | #22 PBI-17 Planned absence rule |  |
|  |  |  |  | #23 PBI-18 Missing PD and calendar weeks |  |
|  |  |  |  | #24 PBI-19 Rejection reason and decider |  |
|  |  |  |  | #25 PBI-20 Start and end times, rest rule |  |
|  |  |  |  | #26 PBI-21 Administrator dashboard |  |

This table is a snapshot; the issues are the live board. The backlog with story points is in [product-backlog.md](product-backlog.md).

## Sprints (milestones)

| Milestone | Goal | Sprint document |
|---|---|---|
| Sprint 0 | Vision, backlog, domain, roles, ADRs, working environment | [sprint-0.md](sprint-0.md) |
| Sprint 1 | Walking skeleton: a consultant can book time, data in MySQL | [sprint-1.md](sprint-1.md) |
| Sprint 2 | Bookings follow the rules, budget per order | [sprint-2.md](sprint-2.md) |
| Sprint 3 | Forecast, follow-up list, proof of work, mockup, API docs, README | [sprint-3.md](sprint-3.md) |
| Freeze | Run check from a fresh clone, final review, no new features | [sprint-3.md](sprint-3.md) |

## Drag-and-drop board (GitHub Projects)

The same cards are shown as a GitHub Project board: https://github.com/users/rayanuahmed5/projects/3

The project's **Status** field has the options Backlog, To do, Doing, Review, Done (and Dropped). It mirrors the `status:` labels of the issues. When a card moves on the board, change its label too, so the issue list and the board agree.

Which card goes where is in the table above.
