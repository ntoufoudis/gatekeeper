<?php

use Illuminate\Support\Facades\Schema;

it('creates gatekeeper_permissions table with correct columns', function () {
    expect(Schema::hasTable('gatekeeper_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_permissions', [
            'id',
            'name',
            'label',
            'guard',
            'group',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates gatekeeper_roles table with correct columns', function () {
    expect(Schema::hasTable('gatekeeper_roles'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_roles', [
            'id',
            'name',
            'label',
            'guard',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates gatekeeper_role_permissions pivot table', function () {
    expect(Schema::hasTable('gatekeeper_role_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_role_permissions', [
            'role_id',
            'permission_id',
        ]))->toBeTrue();
});

it('creates gatekeeper_role_user morph pivot table', function () {
    expect(Schema::hasTable('gatekeeper_role_user'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_role_user', [
            'role_id',
            'user_id',
            'user_type',
        ]))->toBeTrue();
});

it('creates gatekeeper_permission_user morph pivot table', function () {
    expect(Schema::hasTable('gatekeeper_permission_user'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_permission_user', [
            'permission_id',
            'user_id',
            'user_type',
            'granted',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates gatekeeper_sync_log table', function () {
    expect(Schema::hasTable('gatekeeper_sync_log'))->toBeTrue()
        ->and(Schema::hasColumns('gatekeeper_sync_log', [
            'id',
            'summary',
            'synced_at',
        ]))->toBeTrue();
});
