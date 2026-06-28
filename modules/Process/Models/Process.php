<?php

declare(strict_types=1);

namespace Modules\Process\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\ProcedureSetting\Models\ProcedureSetting;
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
        'procedure_setting_id',
        'metadata',
    ];

    protected $casts = [
        'id'                   => 'string',
        'processable_id'       => 'string',
        'procedure_setting_id' => 'string',
        'status'               => ProcessStatus::class,
        'template_snapshot'    => 'array',
        'metadata'             => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        Relation::morphMap([
            'client_request'        => \Modules\ClientRequest\Models\ClientRequest::class,
            'employee_task' => \Modules\EmployeeTask\Models\EmployeeTaskRequest::class,
            'project_notification_task' => \Modules\EmployeeTask\Models\EmployeeTaskRequest::class,
        ]);
    }

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

    public function procedureSetting(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id');
    }
}
