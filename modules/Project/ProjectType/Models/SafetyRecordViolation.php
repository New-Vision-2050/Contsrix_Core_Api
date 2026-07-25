<?php

namespace Modules\Project\ProjectType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SafetyRecordViolation extends Pivot
{
    use UuidTrait;

    protected $table = 'safety_record_violation';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'safety_record_id',
        'violation_id',
        'weight',
        'status',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];
}
