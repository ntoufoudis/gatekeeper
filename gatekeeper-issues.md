# Gatekeeper — GitHub Issues

> `ntoufoudis/gatekeeper` · Permissions as code for Laravel


## Summary

| Milestone                 | Issues  |
|---------------------------|---------|
| v0.1 — Foundation         | 10      |
| v0.2 — ABAC + Hierarchy   | 5       |
| v0.3 — Blade UI + Cache   | 7       |
| v0.4 — Polish + Ecosystem | 6       |
| **Total**                 | **28**  |

## Labels

| Label                | Color     | Description                                    |
|----------------------|-----------|------------------------------------------------|
| `type: feature`      | `#7F77DD` | New functionality                              |
| `type: bug`          | `#E24B4A` | Something is broken                            |
| `type: dx`           | `#1D9E75` | Developer experience improvement               |
| `type: docs`         | `#378ADD` | Documentation                                  |
| `type: test`         | `#639922` | Test coverage                                  |
| `type: refactor`     | `#888780` | Internal improvement, no behaviour change      |
| `type: security`     | `#D85A30` | Security-related concern                       |
| `component: sync`    | `#CECBF6` | Sync engine and differ                         |
| `component: runtime` | `#9FE1CB` | Gate integration, policy bridge, ABAC resolver |
| `component: ui`      | `#FAC775` | Blade admin panel                              |
| `component: cache`   | `#F4C0D1` | Permission cache layer                         |
| `component: cli`     | `#B5D4F4` | Artisan commands                               |
| `component: db`      | `#C0DD97` | Migrations and schema                          |
| `good first issue`   | `#0F6E56` | Good entry point for new contributors          |
| `help wanted`        | `#854F0B` | Extra attention needed                         |
| `breaking change`    | `#A32D2D` | Introduces a breaking API change               |
| `discussion`         | `#D3D1C7` | Needs design discussion before work begins     |

---

## v0.1 — Foundation

> Permissions as code, core sync engine, and basic RBAC. The 'migrations for permissions' core that everything else builds on.

### 1. Package skeleton: composer.json, service provider, config, and autoloading

**Labels:** `type: feature` `component: cli`

## Overview
Scaffold the initial package structure so it can be required via Composer and auto-discovered by Laravel.

## Acceptance criteria
- [ ] `composer.json` with `ntoufoudis/gatekeeper` package name, PHP 8.2+ and Laravel 11/12/13 constraints
- [ ] `GatekeeperServiceProvider` registered under `extra.laravel.providers`
- [ ] `config/gatekeeper.php` published with: definition paths, guard, cache settings, UI toggle
- [ ] `src/`, `database/migrations/`, `resources/views/`, `tests/` directory structure in place
- [ ] `phpunit.xml` / `pest.php` configured for Pest 4
- [ ] GitHub Actions CI workflow runs `pest` on PHP 8.2 + 8.3 + 8.4 + 8.5, Laravel 11 + 12 + 13

### 2. Database migrations: permissions, roles, and pivot tables

**Labels:** `type: feature` `component: db`

## Overview
Create the four core tables that back Gatekeeper's runtime.

## Tables
- `gatekeeper_permissions` — `id`, `name` (unique), `label`, `guard`, `group`, `timestamps`
- `gatekeeper_roles` — `id`, `name` (unique), `label`, `guard`, `timestamps`
- `gatekeeper_role_permissions` — pivot: `role_id`, `permission_id`
- `gatekeeper_role_user` — pivot: `role_id`, `user_id` (morphable: `user_type`)
- `gatekeeper_permission_user` — direct grants/revocations: `permission_id`, `user_id`, `user_type`, `granted` (bool), `timestamps`

## Acceptance criteria
- [ ] All migrations are in `database/migrations/` with correct timestamps
- [ ] Morph columns used for user pivots to support non-standard user models
- [ ] Foreign key constraints with `cascadeOnDelete`
- [ ] Published via `php artisan vendor:publish --tag=gatekeeper-migrations`
- [ ] Pest tests verify tables exist after migration

### 3. PermissionGroup base class and definition file loading

**Labels:** `type: feature` `component: sync`

## Overview
Define the `PermissionGroup` contract that developers extend to declare permissions as PHP classes, and the loader that discovers them from configured paths.

## API
```php
class PostPermissions extends PermissionGroup
{
    public string $guard = 'web';
    public string $group = 'Content';

    public function permissions(): array
    {
        return [
            'posts.view'   => 'View published posts',
            'posts.create' => 'Create new posts',
        ];
    }
}
```

## Acceptance criteria
- [ ] `PermissionGroup` abstract class with `permissions()` abstract method
- [ ] `$guard` and `$group` properties with sensible defaults
- [ ] `DefinitionLoader` discovers all classes extending `PermissionGroup` from configured paths
- [ ] Loader is recursive (supports subdirectories)
- [ ] Pest tests cover loading, guard filtering, and empty directory edge cases

### 4. RoleDefinition base class with permission assignment

**Labels:** `type: feature` `component: sync`

## Overview
Define the `RoleDefinition` contract for declaring roles and their assigned permissions as PHP classes.

## API
```php
class EditorRole extends RoleDefinition
{
    public string $name = 'editor';
    public string $label = 'Content Editor';
    public string $guard = 'web';

    public function permissions(): array
    {
        return ['posts.view', 'posts.create', 'posts.edit'];
    }
}
```

## Acceptance criteria
- [ ] `RoleDefinition` abstract class with `permissions()` abstract method
- [ ] `$name`, `$label`, `$guard` properties
- [ ] `DefinitionLoader` also discovers `RoleDefinition` subclasses
- [ ] Validation: all permission names referenced in a role must exist in a `PermissionGroup` (warn, don't hard-fail)
- [ ] Pest tests cover role loading and unknown-permission warnings

### 5. Sync engine: differ and gatekeeper:sync Artisan command

**Labels:** `type: feature` `component: sync` `component: cli`

## Overview
The core value of Gatekeeper — a differ that computes what needs to change, and an Artisan command that applies it safely.

## Differ logic
- Compare loaded definitions against current DB state
- Produce three sets: `toCreate`, `toUpdate`, `toDelete`
- For deletions: check if any users have the permission/role assigned, warn if so

## Command
```
php artisan gatekeeper:sync
php artisan gatekeeper:sync --dry-run   # preview only, no DB writes
php artisan gatekeeper:sync --force     # skip confirmation prompt
```

## Acceptance criteria
- [ ] `Differ` class produces accurate create/update/delete sets
- [ ] Dry-run outputs a formatted table of pending changes, exits 0
- [ ] Sync is idempotent — running twice is a no-op on second run
- [ ] `PermissionSynced` and `RoleSynced` events dispatched after each change
- [ ] Sync writes a row to `gatekeeper_sync_log` (id, summary JSON, synced_at)
- [ ] Pest tests cover: fresh sync, no-op, additions, removals, dry-run output

### 6. HasPermissions and HasRoles traits for User model

**Labels:** `type: feature` `component: runtime`

## Overview
Provide drop-in traits so the User model can check and manage permissions and roles.

## API
```php
$user->hasPermission('posts.edit');
$user->hasRole('editor');
$user->assignRole('editor');
$user->revokeRole('editor');
$user->grantPermission('posts.delete');  // direct grant
$user->revokePermission('posts.delete'); // direct revoke
$user->getAllPermissions();              // merged from roles + direct
$user->syncRoles(['editor', 'viewer']);
```

## Acceptance criteria
- [ ] `HasPermissions` and `HasRoles` traits in `src/Concerns/`
- [ ] `HasGatekeeperAccess` convenience trait that includes both
- [ ] Eloquent relationships: `roles()`, `directPermissions()`
- [ ] All mutation methods bust the user's permission cache
- [ ] Pest tests cover all public methods including edge cases (no roles, empty permissions)

### 7. Laravel Gate registration on service provider boot

**Labels:** `type: feature` `component: runtime`

## Overview
Register all Gatekeeper permissions with Laravel's Gate on service provider boot, so `Gate::allows()`, `@can`, and `can()` work natively without any extra setup.

## Acceptance criteria
- [ ] `GatekeeperServiceProvider::boot()` calls `Gate::define()` for every permission in DB
- [ ] Gate callbacks delegate to `HasPermissions::hasPermission()` (respects cache)
- [ ] Works with multiple guards
- [ ] Super-admin bypass: configurable `before` callback (e.g. `is_super_admin` flag on user)
- [ ] Gracefully skips if `gatekeeper_permissions` table does not yet exist (pre-migration)
- [ ] Pest feature tests: `Gate::allows()`, `@can` Blade directive, `can` middleware

### 8. gatekeeper:check Artisan command — inspect a user's resolved permissions

**Labels:** `type: feature` `component: cli` `good first issue`

## Overview
A diagnostic command to inspect what permissions and roles a specific user has at runtime — essential for debugging.

## Usage
```
php artisan gatekeeper:check --user=42
php artisan gatekeeper:check --user=42 --permission=posts.edit
php artisan gatekeeper:check --user=42 --guard=api
```

## Output
Formatted table showing: roles assigned, direct grants/revocations, resolved permission list, and for `--permission` flag: GRANTED / DENIED with reason (role, direct grant, ABAC policy).

## Acceptance criteria
- [ ] `--user` accepts ID or email
- [ ] Shows role-sourced vs directly-granted permissions distinctly
- [ ] `--permission` flag explains the resolution chain
- [ ] Pest tests mock the resolver and assert output format

### 9. Pest test suite: foundational coverage for v0.1

**Labels:** `type: test`

## Overview
Ensure all v0.1 components have thorough Pest 4 test coverage before tagging the release.

## Test areas
- [ ] `DefinitionLoader` — discovery, recursion, guard filtering
- [ ] `Differ` — create/update/delete sets, idempotency
- [ ] `gatekeeper:sync` command — dry-run, force, event dispatch, log entry
- [ ] `HasPermissions` / `HasRoles` — all trait methods
- [ ] Gate registration — `Gate::allows()`, `@can`, middleware
- [ ] `gatekeeper:check` command output
- [ ] Migration schema assertions

## Notes
Use Pest's `arch()` tests to enforce that all `PermissionGroup` subclasses declare `$guard` and that `RoleDefinition` subclasses only reference known permissions.

### 10. Arch tests: enforce package conventions via Pest

**Labels:** `type: test` `type: dx`

## Overview
Use Pest's `arch()` API to enforce structural rules across the codebase, preventing regressions as contributors add code.

## Rules to enforce
- [ ] All classes in `src/Definitions/` are abstract or `final`
- [ ] No class in `src/` depends on `Illuminate\Http` (keep runtime free of HTTP concerns except middleware)
- [ ] All `AttributePolicy` subclasses in the configured policy path implement at least one method
- [ ] `PermissionGroup` subclasses always declare `$guard`
- [ ] No `dd()`, `dump()`, `ray()` calls in `src/`
- [ ] All public methods on traits have `@throws` or return types documented

---

## v0.2 — ABAC + Hierarchy

> Attribute-based access control, role inheritance, direct user permission grants, and guard-scoped permissions.

### 1. AttributePolicy base class for ABAC rules

**Labels:** `type: feature` `component: runtime`

## Overview
Introduce attribute-based access control via a `AttributePolicy` base class, allowing context-sensitive rules beyond simple role/permission checks.

## API
```php
class PostPolicy extends AttributePolicy
{
    public function edit(User $user, Post $post): bool
    {
        return $user->id === $post->author_id
            || $user->hasPermission('posts.edit');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.delete')
            && ! $post->is_locked;
    }
}
```

## Acceptance criteria
- [ ] `AttributePolicy` abstract base class in `src/Policies/`
- [ ] Auto-registered with Laravel's `Gate` on service provider boot (same as standard Laravel policies)
- [ ] `DefinitionLoader` discovers `AttributePolicy` subclasses from configured path
- [ ] Policies are NOT synced to DB — they are code-only, evaluated at runtime
- [ ] Works alongside RBAC checks (resolution order: deny > direct grant > role > ABAC)
- [ ] Pest tests cover: owner check, permission fallthrough, `before()` super-admin bypass

### 2. Role inheritance: roles can extend other roles

**Labels:** `type: feature` `component: sync` `component: runtime`

## Overview
Allow roles to inherit all permissions from parent roles, enabling hierarchical role structures.

## API
```php
class SeniorEditorRole extends RoleDefinition
{
    public string $name = 'senior-editor';

    public function permissions(): array
    {
        return ['posts.publish', 'posts.delete'];
    }

    public function inherits(): array
    {
        return ['editor']; // inherits all editor permissions
    }
}
```

## Acceptance criteria
- [ ] `inherits()` method on `RoleDefinition` (returns array of role names)
- [ ] `gatekeeper:sync` resolves and stores the full flattened permission set per role
- [ ] Circular inheritance detected and rejected with a clear error
- [ ] `HasPermissions::hasPermission()` resolves inherited permissions correctly
- [ ] `gatekeeper:check` shows inherited permissions distinctly from direct ones
- [ ] Pest tests: single inheritance, multi-level chain, circular detection

### 3. Direct user permission grants and revocations

**Labels:** `type: feature` `component: runtime`

## Overview
Allow specific permissions to be granted to or revoked from individual users outside of roles, with an explicit denial mechanism.

## Behaviour
- `grantPermission('posts.delete')` — user has this permission regardless of roles
- `revokePermission('posts.delete')` — user is explicitly denied this permission regardless of roles
- `clearPermission('posts.delete')` — removes the direct record, falls back to role resolution

## Acceptance criteria
- [ ] `granted` boolean column on `gatekeeper_permission_user` (true = grant, false = explicit deny)
- [ ] `HasPermissions` methods: `grantPermission()`, `revokePermission()`, `clearPermission()`
- [ ] Resolution order enforced: explicit deny > explicit grant > role-inherited
- [ ] `getAllPermissions()` returns merged set with source annotation
- [ ] Pest tests cover all three states and resolution order interactions

### 4. Guard-scoped permissions

**Labels:** `type: feature` `component: runtime` `good first issue`

## Overview
Ensure all permission checks are guard-aware, so `web` and `api` guards have independent permission sets.

## Acceptance criteria
- [ ] `$guard` property on `PermissionGroup` and `RoleDefinition` respected throughout sync
- [ ] `HasPermissions::hasPermission()` accepts optional `$guard` parameter, defaults to current auth guard
- [ ] `gatekeeper:sync` can be scoped with `--guard=api`
- [ ] `gatekeeper:check` shows per-guard breakdown
- [ ] Pest tests: same permission name on different guards treated as distinct

### 5. RequirePermission and RequireRole middleware

**Labels:** `type: feature` `component: runtime` `good first issue`

## Overview
Ship first-class HTTP middleware so routes can be protected without manual `Gate::authorize()` calls.

## Usage
```php
Route::get('/posts/create', ...)->middleware('gatekeeper.permission:posts.create');
Route::get('/admin', ...)->middleware('gatekeeper.role:admin');
```

## Acceptance criteria
- [ ] `RequirePermission` middleware: checks `hasPermission()`, aborts 403 on failure
- [ ] `RequireRole` middleware: checks `hasRole()`, aborts 403 on failure
- [ ] Both registered in `GatekeeperServiceProvider` as named middleware
- [ ] Support comma-separated values: `gatekeeper.permission:posts.create,posts.edit` (OR logic)
- [ ] Configurable redirect vs JSON 403 response based on `Accepts` header
- [ ] Pest feature tests with real routes

---

## v0.3 — Blade UI + Cache

> Zero-JS admin panel, per-user permission caching with auto-invalidation, and sync log viewer.

### 1. Permission cache layer with per-user invalidation

**Labels:** `type: feature` `component: cache`

## Overview
Cache each user's resolved permission set to avoid repeated DB queries on every request.

## Design
- Cache key: `gatekeeper:permissions:{guard}:{user_id}`
- TTL: configurable, default 3600s
- Tagged cache when driver supports it (`gatekeeper`, `gatekeeper:user:{user_id}`)
- Bust on: `assignRole`, `revokeRole`, `grantPermission`, `revokePermission`, `gatekeeper:sync`

## Acceptance criteria
- [ ] `PermissionCache` service wrapping Laravel's Cache
- [ ] Cache hit path skips DB entirely
- [ ] All mutation methods in `HasPermissions`/`HasRoles` call `PermissionCache::forget()`
- [ ] `gatekeeper:sync` flushes entire `gatekeeper` cache tag (or full cache if tags unsupported)
- [ ] Cache can be disabled via config for testing/debugging
- [ ] Pest tests: cache hit, bust-on-assign, bust-on-sync, tag-unsupported fallback

### 2. Blade UI: role manager (list, create, edit, assign permissions)

**Labels:** `type: feature` `component: ui`

## Overview
The primary admin screen for managing roles — without requiring any JavaScript build step.

## Routes (all under configurable prefix, default `/gatekeeper`)
```
GET  /gatekeeper/roles
GET  /gatekeeper/roles/create
POST /gatekeeper/roles
GET  /gatekeeper/roles/{role}/edit
PUT  /gatekeeper/roles/{role}
DEL  /gatekeeper/roles/{role}
```

## Acceptance criteria
- [ ] Index: paginated role list with permission count badge
- [ ] Create/Edit: name, label, guard, and checkboxes for permissions (grouped by `PermissionGroup`)
- [ ] Roles created via UI are marked as `ui_managed = true` so sync doesn't delete them
- [ ] Delete: warns if users are currently assigned to the role
- [ ] All views extend a `gatekeeper::layout` Blade component (publishable)
- [ ] Routes protected by configurable middleware (default: `auth`)
- [ ] Pest browser/HTTP tests covering CRUD flows

### 3. Blade UI: user assignment screen

**Labels:** `type: feature` `component: ui`

## Overview
Allow admins to assign roles and direct permissions to individual users from the UI.

## Routes
```
GET  /gatekeeper/users
GET  /gatekeeper/users/{user}
PUT  /gatekeeper/users/{user}/roles
PUT  /gatekeeper/users/{user}/permissions
```

## Acceptance criteria
- [ ] User list: searchable, shows assigned role count
- [ ] User detail: current roles (checkboxes), direct permission overrides (grant/deny/clear per permission)
- [ ] Changes bust the user's permission cache immediately
- [ ] Resolves user model from config (not hardcoded to `App\Models\User`)
- [ ] Pest HTTP tests covering role sync and permission toggle

### 4. Blade UI: permission browser — who has what

**Labels:** `type: feature` `component: ui` `good first issue`

## Overview
A read-only screen that answers "which users have permission X?" — critical for audits.

## Routes
```
GET /gatekeeper/permissions
GET /gatekeeper/permissions/{permission}
```

## Acceptance criteria
- [ ] Permission index: grouped by `PermissionGroup`, shows role count and direct-grant count per permission
- [ ] Permission detail: lists roles that include it, and users with direct grants
- [ ] Filterable by guard
- [ ] Pest HTTP tests

### 5. Blade UI: sync log viewer

**Labels:** `type: feature` `component: ui` `component: sync`

## Overview
Expose the `gatekeeper_sync_log` table as a readable history screen so teams can see what changed and when.

## Routes
```
GET /gatekeeper/sync-log
GET /gatekeeper/sync-log/{entry}
```

## Acceptance criteria
- [ ] Log index: paginated list with timestamp, who triggered it (if authenticated), summary (N added, M updated, K removed)
- [ ] Log detail: full JSON diff rendered as a readable table (added permissions in green, removed in red, changed in amber)
- [ ] Dry-run entries marked distinctly (not applied)
- [ ] Pest HTTP tests

### 6. Publishable Blade layout and view components

**Labels:** `type: feature` `component: ui` `type: dx`

## Overview
Make the Blade UI customisable so teams can match their app's design without forking the package.

## Acceptance criteria
- [ ] `gatekeeper::layout` component publishable via `vendor:publish --tag=gatekeeper-views`
- [ ] Layout accepts `title` and `breadcrumbs` slots
- [ ] All views use component-based approach (no monolithic templates)
- [ ] `config('gatekeeper.ui.layout')` allows overriding the layout component entirely
- [ ] Default layout is minimal: nav sidebar with links to roles, users, permissions, sync log
- [ ] Dark mode support via Tailwind's `dark:` prefix (CDN, no build step)

### 7. Security: prevent permission escalation via UI

**Labels:** `type: security` `component: ui`

## Overview
Ensure the Blade UI cannot be used to escalate privileges — e.g. a user with `gatekeeper.manage` cannot grant themselves permissions they don't already have.

## Acceptance criteria
- [ ] `gatekeeper.manage` permission required for all UI routes (configurable)
- [ ] Non-super-admins cannot assign permissions higher than their own resolved set
- [ ] Audit log (Chronicle if available, fallback to Laravel log) records all UI-triggered changes with actor ID
- [ ] Rate limiting on assignment endpoints
- [ ] Pest tests for escalation attempt scenarios

---

## v0.4 — Polish + Ecosystem

> Developer ergonomics, Spatie migration tooling, Filament add-on, temporal permissions, Chronicle integration, and documentation site.

### 1. Artisan generators: make:permission and make:role

**Labels:** `type: feature` `component: cli` `type: dx` `good first issue`

## Overview
Developer-experience commands to scaffold new definition files, matching the `make:model` convention.

## Usage
```
php artisan gatekeeper:make:permission PostPermissions --group="Content"
php artisan gatekeeper:make:role EditorRole --inherits=viewer
```

## Acceptance criteria
- [ ] `make:permission` generates a stub `PermissionGroup` subclass in the configured definitions path
- [ ] `make:role` generates a stub `RoleDefinition` subclass with `--inherits` option
- [ ] Stubs are publishable (`vendor:publish --tag=gatekeeper-stubs`)
- [ ] Commands warn if the target file already exists
- [ ] Pest tests assert generated file contents

### 2. Spatie Permission migration command

**Labels:** `type: feature` `component: cli` `type: dx`

## Overview
A one-shot migration command for teams moving from `spatie/laravel-permission` to Gatekeeper, preserving all roles, permissions, and user assignments.

## Usage
```
php artisan gatekeeper:import-spatie --dry-run
php artisan gatekeeper:import-spatie
```

## Acceptance criteria
- [ ] Detects Spatie tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`)
- [ ] Imports all roles and permissions into Gatekeeper tables
- [ ] Preserves user-role and user-permission assignments
- [ ] Generates definition PHP files for each imported role and permission group
- [ ] Dry-run outputs what would be imported without writing
- [ ] Marks all imported records as `ui_managed = false` (i.e. code-managed going forward)
- [ ] Pest tests with fixture Spatie data

### 3. Temporal permissions: expires_at on direct grants

**Labels:** `type: feature` `component: runtime` `component: db`

## Overview
Allow direct permission grants to expire automatically — useful for temporary access elevation.

## API
```php
$user->grantPermission('posts.delete', expiresAt: now()->addDays(7));
$user->hasPermission('posts.delete'); // false after expiry
```

## Acceptance criteria
- [ ] `expires_at` nullable timestamp on `gatekeeper_permission_user`
- [ ] `hasPermission()` filters out expired grants at query time
- [ ] `gatekeeper:prune` Artisan command removes expired rows
- [ ] `PrunableGrants` can be scheduled via Laravel's scheduler
- [ ] UI shows expiry date on the user assignment screen
- [ ] Pest tests: active, expired, null (no expiry) states

### 4. Optional Chronicle audit integration

**Labels:** `type: feature` `component: runtime` `type: dx`

## Overview
When `castwork/laravel-chronicle` is installed, automatically log all Gatekeeper mutations as audit entries.

## Events to log
- `gatekeeper:sync` applied — with full diff summary
- Role assigned / revoked from user
- Direct permission granted / revoked
- Role created / updated / deleted via UI

## Acceptance criteria
- [ ] Chronicle integration is opt-in via `config('gatekeeper.audit.driver') = 'chronicle'`
- [ ] No hard dependency on Chronicle — detected at runtime via `class_exists()`
- [ ] `GatekeeperChronicleListener` subscribes to all relevant events
- [ ] Each log entry includes actor (authenticated user), target (affected user/role), and payload
- [ ] Pest tests mock Chronicle and assert correct entries are written

### 5. Optional Filament plugin: castwork/gatekeeper-filament

**Labels:** `type: feature` `component: ui` `discussion`

## Overview
A separate package providing a Filament v3 plugin that replaces the Blade UI with a full Filament admin panel experience.

## Scope (separate repo: `castwork/gatekeeper-filament`)
- [ ] Filament Resource for roles (with permission assignment repeater)
- [ ] Filament Resource for user permission management
- [ ] Filament Page for permission browser
- [ ] Filament Page for sync log
- [ ] `GatekeeperPlugin` class for registration in `AdminPanelProvider`

## Acceptance criteria
- [ ] Separate `composer.json` with `castwork/gatekeeper` and `filament/filament` as dependencies
- [ ] All Gatekeeper config respected (guard, model, middleware)
- [ ] Published to Packagist as `castwork/gatekeeper-filament`
- [ ] README documents installation alongside core Gatekeeper

### 6. Documentation site (VitePress)

**Labels:** `type: docs`

## Overview
Comprehensive documentation covering installation, configuration, all concepts, and the API.

## Sections
- [ ] Getting started (install, migrate, first sync)
- [ ] Core concepts (permission groups, role definitions, ABAC policies)
- [ ] Sync engine (dry-run, events, sync log)
- [ ] Runtime (Gate, middleware, traits API)
- [ ] UI guide (Blade panel, customisation)
- [ ] Cache (configuration, invalidation)
- [ ] Migrating from Spatie Permission
- [ ] Chronicle integration
- [ ] API reference (all public methods)
- [ ] Contributing guide

## Acceptance criteria
- [ ] VitePress site in `/docs`
- [ ] Deployed to GitHub Pages via Actions on push to `main`
- [ ] All code examples tested (copy-pasteable, not pseudocode)
