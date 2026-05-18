<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTaskSession extends Model
{
    use UuidTrait;

    protected $table = 'employee_task_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'employee_task_request_id',
        'company_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'source',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'notes',
    ];

    protected $casts = [
        'id'              => 'string',
        'start_latitude'  => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'end_latitude'    => 'decimal:7',
        'end_longitude'   => 'decimal:7',
        'start_time'      => 'datetime',
        'end_time'        => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id');
    }

    public function isActive(): bool
    {
        return $this->end_time === null;
    }
}
