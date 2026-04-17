<?php

namespace Ntoufoudis\Gatekeeper\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'dry_run' => 'boolean',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /** @var string $table */
        $table = config('gatekeeper.tables.sync_log', 'gatekeeper_sync_log');

        $this->setTable($table);
    }
}
