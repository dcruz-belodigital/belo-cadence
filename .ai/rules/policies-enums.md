---
paths:
  - 'app/{Policies,Enums}/**'
---

# Policies Enums

## Permissions come from the PermissionName enum, through roles only
`App\Enums\PermissionName` is the only source of permission names; `PermissionSeeder` mirrors it into the database and the roles UI can never invent one. Policies authorize with `$user->can(PermissionName::X->value)` (Spatie hooks `Gate::before`, so dotted names never collide with policy abilities). Import policies additionally require the underlying create and update permissions, so an import can never bypass a mutation the user could not perform by hand — and `ImportClientsRequest`/`ImportUsersRequest` authorize in the request so an unauthorised upload is refused before the file is read. `App\Support\EssentialAdministration` answers the "what if" questions that stop the application being left with nobody able to manage users and roles; use it from policies and validation rules rather than duplicating the reasoning.
