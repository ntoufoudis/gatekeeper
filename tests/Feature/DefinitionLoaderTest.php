<?php

use Ntoufoudis\Gatekeeper\Loader\DefinitionLoader;
use Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions\PostPermissions;
use Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions\Sub\CommentPermissions;

$fixturesPath = __DIR__.'/../Fixtures/Permissions';

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
