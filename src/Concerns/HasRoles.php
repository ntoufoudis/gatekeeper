<?php

namespace Ntoufoudis\Gatekeeper\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ntoufoudis\Gatekeeper\Models\Role;

trait HasRoles
{
    /**
     * @return MorphToMany<Role, $this>
     */
    public function roles(): MorphToMany
    {
        /** @var string $table */
        $table = config('gatekeeper.tables.role_user', 'gatekeeper_role_user');

        return $this->morphToMany(
            Role::class,
            'user',
            $table,
            'user_id',
            'role_id',
        );
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function assignRole(string $name): void
    {
        $role = Role::where('name', $name)->firstOrFail();

        if (! $this->hasRole($name)) {
            $this->roles()->attach($role);
        }
    }

    public function revokeRole(string $name): void
    {
        $role = Role::where('name', $name)->first();

        if ($role) {
            $this->roles()->detach($role);
        }
    }

    /**
     * @param  string[]  $names
     */
    public function syncRoles(array $names): void
    {
        $ids = Role::whereIn('name', $names)->pluck('id');

        $this->roles()->sync($ids);
    }
}
