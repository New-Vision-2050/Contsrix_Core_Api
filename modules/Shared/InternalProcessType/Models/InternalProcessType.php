<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class InternalProcessType extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'internal_process_types';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'entity_type',
        'name',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'id'         => 'string',
        'company_id' => 'string',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'settings'   => 'array',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getSetting(InternalProcessCondition $condition): mixed
    {
        return $this->settings[$condition->value] ?? null;
    }

    public function allowsDuringShift(): bool
    {
        return (bool) ($this->settings[InternalProcessCondition::AllowDuringShift->value] ?? false);
    }
}
