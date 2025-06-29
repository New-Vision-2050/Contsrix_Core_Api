<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Models;

use Modules\Country\Models\Country;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Modules\SubEntity\Models\SubEntity;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyAccessProgram extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function companyFields(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyField::class,
            'company_access_program_field',
            'company_access_program_id',
            'company_field_id'
        );
    }

    public function companyTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyType::class,
            'company_access_program_type',
            'company_access_program_id',
            'company_type_id'
        );
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'company_access_program_country',
            'company_access_program_id',
            'country_id'
        );
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            Program::class,
            'company_access_program_program',
            'company_access_program_id',
            'program_id'
        );
    }

    public function subEntities(): BelongsToMany
    {
        return $this->belongsToMany(
            SubEntity::class,
            'company_access_program_sub_entity',
            'company_access_program_id',
            'sub_entity_id'
        );
    }
}
