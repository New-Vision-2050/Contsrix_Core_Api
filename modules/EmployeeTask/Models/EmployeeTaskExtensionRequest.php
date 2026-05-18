<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class EmployeeTaskExtensionRequest extends Model
{
    use UuidTrait;

    protected $table = 'employee_task_extension_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'employee_task_request_id',
        'company_id',
        'requested_by',
        'additional_hours',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'id'               => 'string',
        'additional_hours' => 'decimal:2',
        'reviewed_at'      => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
