<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Country\Models\Country;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectContractor extends Model implements HasMedia
{
    use UuidTrait;
    use BelongsToTenant;
    use InteractsWithMedia;

    protected $table = 'project_contractors';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_id',
        'name',
        'number',
        'mobile',
        'notes',
        'is_active',
        'tax_card',
        'commercial_register',
        'activity',
        'email',
        'country_id',
        'logo',
        'project_contractor_id',
        'project_manager_name',
        'project_manager_phone',
        'project_manager_nationality',
        'project_manager_email',
        'safety_officer_name',
        'safety_officer_email',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'project_id' => 'string',
        'country_id' => 'string',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id')->withoutGlobalScopes();
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(ProjectContractorRepresentative::class, 'project_contractor_id')->withoutGlobalScopes();
    }
}
