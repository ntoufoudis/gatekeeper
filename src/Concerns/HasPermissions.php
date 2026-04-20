<?php

namespace Ntoufoudis\Gatekeeper\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ntoufoudis\Gatekeeper\Models\Permission;

trait HasPermissions
{
    /**
     * @return MorphToMany<Permission, $this>
     */
    public function directPermissions(): MorphToMany
    {
        /** @var string $table */
        $table = config('gatekeeper.tables.permission_user', 'gatekeeper_permission_user');

        return $this->morphToMany(
            Permission::class,
            'user',
            $table,
            'user_id',
            'permission_id'
        )->withPivot('granted');
    }

    public function hasPermission(string $name, ?string $guard = null): bool
    {
        // Check explicit deny first
        $direct = $this->directPermissions()
            ->where('name', $name)
            ->first();

        if ($direct !== null) {
            return (bool) $direct->pivot->granted;
        }

        // Check via roles
        return $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('name')
            ->contains($name);
    }

    public function grantPermission(string $name): void
    {
        $permission = Permission::where('name', $name)->firstOrFail();
        $this->directPermissions()->syncWithoutDetaching([
            $permission->id => ['granted' => true],
        ]);
    }

    public function revokePermission(string $name): void
    {
        $permission = Permission::where('name', $name)->firstOrFail();
        $this->directPermissions()->syncWithoutDetaching([
            $permission->id => ['granted' => false],
        ]);
    }

    public function clearPermission(string $name): void
    {
        $permission = Permission::where('name', $name)->first();
        if ($permission) {
            $this->directPermissions()->detach($permission->id);
        }
    }

    /** @return array<int, mixed> */
    public function getAllPermissions(): array
    {
        $denied = $this->directPermissions()
            ->wherePivot('granted', false)
            ->pluck('name')
            ->all();

        $directGrants = $this->directPermissions()
            ->wherePivot('granted', true)
            ->pluck('name')
            ->all();

        $fromRoles = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->all();

        return collect(array_merge($directGrants, $fromRoles))
            ->reject(fn ($p) => in_array($p, $denied, true))
            ->unique()
            ->values()
            ->all();
    }
}
