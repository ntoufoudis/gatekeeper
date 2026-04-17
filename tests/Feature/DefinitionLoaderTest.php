<?php

use Ntoufoudis\Gatekeeper\Loader\DefinitionLoader;
use Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions\PostPermissions;
use Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions\Sub\CommentPermissions;
use Ntoufoudis\Gatekeeper\Tests\Fixtures\Roles\EditorRole;

$fixturesPath = __DIR__.'/../Fixtures/Permissions';
$rolesPath = __DIR__.'/../Fixtures/Roles';

it('discovers PermissionGroup subclasses in a directory', function () use ($fixturesPath) {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups([$fixturesPath]);

    $classes = array_map(fn ($g) => get_class($g), $groups);

    expect($classes)->toContain(PostPermissions::class);
});

it('discovers PermissionGroup subclasses recursively in subdirectories', function () use ($fixturesPath) {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups([$fixturesPath]);

    $classes = array_map(fn ($g) => get_class($g), $groups);
    expect($classes)->toContain(CommentPermissions::class);
});

it('can filter by guard', function () use ($fixturesPath) {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups([$fixturesPath], guard: 'api');

    $classes = array_map(fn ($g) => get_class($g), $groups);
    expect($classes)->toContain(CommentPermissions::class)
        ->not->toContain(PostPermissions::class);
});

it('returns empty array for non-existent directory', function () {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups(['/does/not/exist']);

    expect($groups)->toBeEmpty();
});

it('returns empty array for directory with no PermissionGroup subclasses', function () {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups([__DIR__]);

    expect($groups)->toBeEmpty();
});

it('discovers RoleDefinition subclasses', function () use ($rolesPath) {
    $loader = new DefinitionLoader;
    $roles = $loader->loadRoleDefinitions([$rolesPath]);

    $classes = array_map(fn ($r) => get_class($r), $roles);
    expect($classes)->toContain(
        EditorRole::class
    );
});

it('warns when a role references an unknown permission', function () use ($rolesPath, $fixturesPath) {
    $loader = new DefinitionLoader;
    $groups = $loader->loadPermissionGroups([$fixturesPath]);
    $knownPermissions = collect($groups)
        ->flatMap(fn ($g) => array_keys($g->permissions()))
        ->all();

    $roles = $loader->loadRoleDefinitions([$rolesPath]);

    $warnings = $loader->validateRolePermissions($roles, $knownPermissions);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('nonexistent.permission');
});
