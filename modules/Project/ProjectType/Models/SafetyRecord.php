<?php

namespace Modules\Project\ProjectType\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SafetyRecord extends Model implements HasMedia
{
    use UuidTrait;
    use CustomBelongsToTenant;
    use BaseFilterable;
    use InteractsWithMedia;

    protected $table = 'safety_records';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
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
        'time' => 'string',
        'required_score' => 'decimal:2',
        'earned_score' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('violation_evidence');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }

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
            ->using(SafetyRecordViolation::class)
            ->withPivot('id', 'weight', 'status')
            ->withTimestamps();
    }
    // }
}
