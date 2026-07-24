<?php

namespace Modules\Project\ProjectType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;

class SafetyRecord extends Model
{
    use UuidTrait;

    protected $table = 'safety_records';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'morphable_type',
        'morphable_id',
        'order_type',
        'date',
        'time',
        'required_score',
        'earned_score',
        'percentage',
        'consultant_engineer',
        'consultant',
        'contractor_id',
        'assigned_user_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'required_score' => 'decimal:2',
        'earned_score' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(ProjectContractor::class, 'contractor_id')->withoutGlobalScopes();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withoutGlobalScopes();
    }

    public function morphable(): MorphTo
    {
        return $this->morphTo();
    }

    public function violations(): BelongsToMany
    {
        return $this->belongsToMany(
            Violation::class,
            'safety_record_violation',
            'safety_record_id',
            'violation_id'
        )
        ->withPivot('weight', 'status')
        ->withTimestamps();
    }
}
