<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Ntoufoudis\Gatekeeper\Concerns\HasGatekeeperAccess;
use Ntoufoudis\Gatekeeper\Models\Permission;
use Ntoufoudis\Gatekeeper\Models\Role;

// Inline test user model
class GatekeeperTestUser extends Model
{
    use HasGatekeeperAccess;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function () {
    // Create users table for tests
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
    });

    Permission::create(['name' => 'posts.view', 'label' => 'View Posts', 'guard' => 'web']);
    Permission::create(['name' => 'posts.create', 'label' => 'Create Posts', 'guard' => 'web']);
    Permission::create(['name' => 'posts.delete', 'label' => 'Delete Posts', 'guard' => 'web']);

    $role = Role::create(['name' => 'editor', 'label' => 'Editor', 'guard' => 'web']);
    $role->permissions()->attach(
        Permission::where('name', 'posts.view')->first()
    );
    $role->permissions()->attach(
        Permission::where('name', 'posts.create')->first()
    );
});

afterEach(function () {
    Schema::dropIfExists('users');
});

it('returns false when user has no roles or permissions', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);

    expect($user->hasPermission('posts.view'))->toBeFalse()
        ->and($user->hasRole('editor'))->toBeFalse();
});

it('detects permission via assigned role', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->assignRole('editor');

    expect($user->hasPermission('posts.view'))->toBeTrue()
        ->and($user->hasPermission('posts.delete'))->toBeFalse();
});

it('assigns and revokes roles', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->assignRole('editor');
    expect($user->hasRole('editor'))->toBeTrue();

    $user->revokeRole('editor');
    expect($user->hasRole('editor'))->toBeFalse();
});

it('grants a direct permission to a user', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->grantPermission('posts.delete');

    expect($user->hasPermission('posts.delete'))->toBeTrue();
});

it('revokes a direct permission from a user', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->grantPermission('posts.delete');
    $user->revokePermission('posts.delete');

    expect($user->hasPermission('posts.delete'))->toBeFalse();
});

it('explicit denial overrides role-based grant', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->assignRole('editor');
    $user->revokePermission('posts.view'); // explicit deny

    expect($user->hasPermission('posts.view'))->toBeFalse();
});

it('getAllPermissions returns merged set from roles and direct grants', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->assignRole('editor');
    $user->grantPermission('posts.delete');

    $all = $user->getAllPermissions();
    expect($all)->toContain('posts.view')
        ->toContain('posts.create')
        ->toContain('posts.delete');
});

it('syncRoles replaces all roles', function () {
    $user = GatekeeperTestUser::create(['name' => 'Alice']);
    $user->assignRole('editor');

    Role::create(['name' => 'viewer', 'label' => 'Viewer', 'guard' => 'web']);
    $user->syncRoles(['viewer']);

    expect($user->hasRole('editor'))->toBeFalse()
        ->and($user->hasRole('viewer'))->toBeTrue();
});
