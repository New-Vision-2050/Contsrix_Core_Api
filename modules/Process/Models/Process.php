<?php

declare(strict_types=1);

namespace Modules\Process\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Process\Enums\ProcessStatus;

class Process extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'processes';

    protected $fillable = [
        'processable_id',
        'processable_type',
        'type',
        'execute_type',
        'status',
        'sort_order',
        'template_snapshot',
    ];

    protected $casts = [
        'id'                 => 'string',
        'processable_id'     => 'string',
        'status'             => ProcessStatus::class,
        'template_snapshot'  => 'array',
    ];

    /**
     * Get the owning processable model (ClientRequest, EmployeeTask, etc.).
     */
    public function processable(): MorphTo
    {
        return $this->morphTo();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'process_id');
    }
}
