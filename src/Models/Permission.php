<?php

namespace Ntoufoudis\Gatekeeper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /** @var string $table */
        $table = config('gatekeeper.tables.permissions', 'gatekeeper_permissions');

        $this->setTable($table);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        /** @var string $table */
        $table = config('gatekeeper.tables.role_permissions', 'gatekeeper_role_permissions');

        return $this->belongsToMany(
            Role::class,
            $table,
            'permission_id',
            'role_id'
        );
    }
}
