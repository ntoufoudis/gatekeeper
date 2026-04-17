<?php

namespace Ntoufoudis\Gatekeeper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /** @var string $table */
        $table = config('gatekeeper.tables.roles', 'gatekeeper_roles');

        $this->setTable($table);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        /** @var string $table */
        $table = config('gatekeeper.tables.role_permissions', 'gatekeeper_role_permissions');

        return $this->belongsToMany(
            Permission::class,
            $table,
            'role_id',
            'permission_id'
        );
    }
}
