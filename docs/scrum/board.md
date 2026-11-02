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

## Board on Mon 2 Nov 2026, 09:45 UTC

| Backlog | To do | Doing | Review | Done | Dropped |
|---|---|---|---|---|---|
| #15 PBI-15 What-if scenario | #19 Fresh-clone run check | #17 Create the Scrum board on GitHub | #18 Click through Proof of work as finance | #1 PBI-01 Project starts from the README | #13 PBI-13 Compose file |
| #16 PBI-16 Follow-up order draft | #20 Final review against the scoring | | | #2 PBI-02 Data model and seed data | |
| | #21 Commits on Tuesday | | | #3 PBI-03 Demo users per role | |
| | | | | #4 PBI-04 Booking form | |
| | | | | #5 PBI-05 Booking rules | |
| | | | | #6 PBI-06 Budget per order | |
| | | | | #7 PBI-07 Permissions on the server | |
| | | | | #8 PBI-08 Forecast | |
| | | | | #9 PBI-09 Follow-up list | |
| | | | | #10 PBI-10 Proof of work | |
| | | | | #11 PBI-11 Mockup | |
| | | | | #12 PBI-12 API documentation | |
| | | | | #14 PBI-14 README run check | |

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

GitHub's Projects boards cannot be created from the tool that built this repository, so the board above uses labels. To get a drag-and-drop view of the same cards, the repository owner can add one in a minute:

1. On GitHub open **Projects**, then **New project**, choose the **Board** template and name it "Pegal Scrum".
2. In the new project press **+ Add item**, type `#` and add all issues of the repository (or turn on the "Auto-add" workflow).
3. Set the board's column field to **Status** with the options Backlog, To do, Doing, Review, Done, and group by it. The card labels show where each issue belongs.
