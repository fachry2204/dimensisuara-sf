# Separate administrator accounts

`user_admin` stores administrator identities. `users` stores customer identities. The `Admin` model extends the common account behavior, but has its own table and session guard/provider (`admin` / `admins`). The existing login form checks the matching account type automatically. Administrators must have at least one role to sign in. Customer models cannot obtain administrative permissions from a matching numeric ID.

`access_user_roles.user_id` now references `user_admin.id`; its name is retained for compatibility. `Role::users()` and permission notification recipients resolve administrator models. Admin creation, reactivation, and revocation use `user_admin`. Revocation removes permissions while retaining the administrator record. New registrations and new administrator accounts must use emails not already registered in the other account table.

Some existing staff have customer profiles and catalogues. These customer identities remain in `users`, independently from the administrator identity. Their IDs may coincide; guards, roles, authorship, and profile relationships explicitly distinguish the tables. A staff member signs in to the administrator account and uses the existing authorized impersonation feature to view a customer workspace. An `Admin` model never loads a customer profile by matching its own numeric ID.

Password recovery retains the existing UI. Admin tokens are isolated in `admin_password_reset_tokens`, with a separate `admins` password broker. Customer recovery continues to use `password_reset_tokens`. Password hashes and account IDs are copied unchanged during migration. Previous administrator sessions use the old customer guard and require signing in again.

Announcements, broadcasts, and reporting imports reference administrator accounts. Logs and release history distinguish `admin_user_id` from the existing customer ID column. Their `user` relationship resolves both account types, including eager loading. Admin notifications and administrator-owned polymorphic files use the `Admin` model type.

## Migration and recovery

Run `2026_10_30_170000_separate_admin_accounts.php` after the earlier project migrations. It copies raw account rows, repoints administrative foreign keys, moves mixed audit references, and removes admin-only identities from `users`. Customer profiles belonging to staff are retained. A rollback stops before merging if administrator and customer identities overlap, preventing accidental account replacement.

The local `dimensisuarasf` migration was backed up under `storage/app/private/backups/`. Verification also found that its customer catalogue and related tables were empty. Empty business-data tables were restored from `cmssoundfresh`, with account author references mapped to the new administrator table. Existing nonempty settings were retained; the source database was not changed. Session, job, notification-delivery queue, and password-reset tables were not replayed.

Tests: `AdminAccountSeparationTest` exercises migration, preserved credentials, guard isolation, overlapping IDs, customer ownership, revoked access, password reset tokens, audit authors, creation/revocation, and rollback. Sidebar, maintenance, system settings, and landing-page regression tests cover the surrounding application.
