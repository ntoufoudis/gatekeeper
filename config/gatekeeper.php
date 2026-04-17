<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Definition Paths
    |--------------------------------------------------------------------------
    | Directories scanned (recursively) for PermissionGroup and RoleDefinition
    | subclasses. These paths are relative to app_path().
    */
    'definitions' => [
        'permissions' => [app_path('Gatekeeper/Permissions')],
        'roles' => [app_path('Gatekeeper/Roles')],
        'policies' => [app_path('Gatekeeper/Policies')],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'gatekeeper',
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-Admin
    |--------------------------------------------------------------------------
    | When set, Gate::before() grants this user all permissions.
    | Set to null to disable.
    */
    'super_admin' => [
        'enabled' => false,
        'gate_callback' => null, // callable: fn($user) => bool
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'enabled' => true,
        'prefix' => 'gatekeeper',
        'middleware' => ['web', 'auth'],
        'layout' => 'gatekeeper::layout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'permissions' => 'gatekeeper_permissions',
        'roles' => 'gatekeeper_roles',
        'role_permissions' => 'gatekeeper_role_permissions',
        'role_user' => 'gatekeeper_role_user',
        'permission_user' => 'gatekeeper_permission_user',
        'sync_log' => 'gatekeeper_sync_log',
    ],
];
