---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions own business operations
Every state-changing operation lives in a `final` invokable Action with one public `__invoke()` and private helpers only. Input arrives as an immutable DTO from `app/Data/**` (entity + DTO when updating: `__invoke(Client $client, UpdateClientData $data)`). Actions wrap multi-write work in `DB::transaction` and record their own audit entry via `RecordAuditAction` — there are deliberately no observers, model boot hooks or event listeners for business behaviour. Controllers stay thin: authorize, convert the Form Request to a DTO, call the Action, redirect. Straightforward read queries stay in the controller; do not wrap them in an Action.
