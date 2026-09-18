---
paths:
  - 'resources/views/**'
---

# Views

## A row's actions live in one menu, and the consequential ones ask first
Every table row offers its actions through `x-table.actions` — a single kebab trigger whose entries are `x-dropdown.item`s. No loose `x-button` in a `<x-table.cell align="right">`; `tests/Feature/DesignSystemTest.php` fails the build on one. The action column's `sr-only` heading is `common.columns.actions`, and the trigger's label is `common.actions.more`.

`x-table.actions` teleports its panel to the body and places it in viewport coordinates, because a table sits in a card that clips to its radius — see the rule about cards clipping popouts. That is also why it closes on scroll and resize, and why it traps focus while open: the panel is at the end of the body, out of the row's tab order.

An action that sends email or changes what a record does next asks before it runs: send now, enable/disable a schedule, activate/deactivate an account, delete, archive, restore. They all use `x-confirm-form` — `trigger-as="menu-item"` inside the menu, the default button trigger on a record's own page — with `variant="primary"` for an ordinary action and the `danger` default for a destructive one. Each question needs `title`, `message` and `confirm` keys in both catalogues.
