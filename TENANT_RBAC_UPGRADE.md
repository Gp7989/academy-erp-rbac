# Tenant-Scoped RBAC Upgrade Guide

## 1) New database schema

- `users.owner_id` (nullable): `NULL` means tenant owner/admin; non-null points to workspace owner.
- `roles.owner_id` (required): tenant-scopes role definitions per admin workspace.
- `admissions.owner_id` (required): tenant-scopes all form data.
- `permissions` remain global (`create_form`, `edit_form`, `view_form`, `delete_form`).

See full schema: `database/auth_db.sql`.

## 2) Migration SQL queries

Run `database/migrations/20260221_tenant_rbac_upgrade.sql` in order.

It:
1. Adds `users.owner_id` self-FK.
2. Adds `roles.owner_id` + unique `(owner_id, role_name)`.
3. Adds + backfills `admissions.owner_id`.
4. Renames permissions to resource-specific actions.

## 3) Role creation logic

- Admin-only route: `settings/manage_roles.php`.
- On create role:
  - insert with `roles.owner_id = current admin id`.
  - role names are unique only in that tenant.

## 4) Role deletion logic

- Admin-only route: `settings/manage_roles.php`.
- Deletes role only when:
  - `roles.id = ?`
  - `roles.owner_id = current admin id`
- Protects owner bootstrap role by blocking delete of `Owner` role.

## 5) Assign permission logic

- Admin-only route: `settings/manage_roles.php`.
- Before assignment, verify role belongs to current tenant (`owner_id`).
- Replace role permissions with selected set in `role_permissions`.
- Permissions remain global records in `permissions`.

## 6) Assign role to user logic

- User creation (`settings/add_user.php`):
  - creates `users.owner_id = admin_id`
  - validates selected role belongs to same admin (`roles.owner_id = admin_id`)
  - assigns via `user_roles`
- Existing users (`settings/users_list.php`):
  - validates target user belongs to admin tenant
  - supports `change_role` and `remove_role`
  - supports direct permission removal from `user_permissions`

## 7) Dashboard filtering logic

- `dashboard/dashboard.php` now queries admissions by tenant:
  - `WHERE admissions.owner_id = tenant_owner_id`
- Shows submitter name (`users.full_name`) for admin-wide visibility.

## 8) Secure CRUD examples

- Create: `academy/academy_form_process.php` inserts both `user_id` + `owner_id`.
- Read: `dashboard/view_admission.php` enforces `id + owner_id`.
- Update: `dashboard/edit_form.php` updates only with `WHERE id = ? AND owner_id = ?`.
- Delete: `dashboard/delete_form.php` deletes only with `WHERE id = ? AND owner_id = ?`.

## 9) Tenant isolation explanation

- A user’s effective tenant is computed by:
  - owner user: own `id`
  - child user: `users.owner_id`
- This value is used consistently in route-level data access predicates.
- Even if permissions allow CRUD, cross-tenant access is blocked by `owner_id` constraints.
- This prevents horizontal privilege escalation by ID guessing.
