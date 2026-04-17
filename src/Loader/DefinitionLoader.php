<?php

namespace Ntoufoudis\Gatekeeper\Loader;

use Ntoufoudis\Gatekeeper\Definitions\Definition;
use Ntoufoudis\Gatekeeper\Definitions\PermissionGroup;
use Ntoufoudis\Gatekeeper\Definitions\RoleDefinition;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Throwable;

class DefinitionLoader
{
    /**
     * @param  string[]  $paths
     * @return PermissionGroup[]
     */
    public function loadPermissionGroups(array $paths, ?string $guard = null): array
    {
        return $this->loadDefinitions($paths, PermissionGroup::class, $guard);
    }

    /**
     * @param  string[]  $paths
     * @return RoleDefinition[]
     */
    public function loadRoleDefinitions(array $paths, ?string $guard = null): array
    {
        return $this->loadDefinitions($paths, RoleDefinition::class, $guard);
    }

    /**
     * @template T of Definition
     *
     * @param  string[]  $paths
     * @param  class-string<T>  $baseClass
     * @return T[]
     */
    private function loadDefinitions(array $paths, string $baseClass, ?string $guard): array
    {
        $instances = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $this->requireFile($file->getPathname());
            }
        }

        $realPaths = array_filter(array_map(fn ($p) => realpath($p), $paths));

        foreach (get_declared_classes() as $class) {
            if (! is_subclass_of($class, $baseClass)) {
                continue;
            }

            try {
                $classFile = (new ReflectionClass($class))->getFileName();
            } catch (ReflectionException) {
                continue;
            }

            if (! $classFile) {
                continue;
            }

            $inScannedPath = false;
            foreach ($realPaths as $realPath) {
                if (str_starts_with($classFile, $realPath.DIRECTORY_SEPARATOR) || $classFile === $realPath) {
                    $inScannedPath = true;
                    break;
                }
            }

            if (! $inScannedPath) {
                continue;
            }

            $instance = new $class;

            if ($guard !== null && $instance->guard !== $guard) {
                continue;
            }

            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * @param  RoleDefinition[]  $roles
     * @param  string[]  $knownPermissions
     * @return string[] warning messages
     */
    public function validateRolePermissions(array $roles, array $knownPermissions): array
    {
        $warnings = [];

        foreach ($roles as $role) {
            foreach ($role->permissions() as $permission) {
                if (! in_array($permission, $knownPermissions, true)) {
                    $warnings[] = sprintf(
                        'Role "%s" references unknown permission "%s".',
                        $role->name,
                        $permission
                    );
                }
            }
        }

        return $warnings;
    }

    private function requireFile(string $path): void
    {
        try {
            require_once $path;
        } catch (Throwable) {
            // skip unloadable files
        }
    }
}
