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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Package\Models\Package;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class CompanyAccessProgram extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasRelationships;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'bool',
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

    public function programs(): HasMany
    {
        return $this->hasMany(
            CompanyAccessProgramProgram::class,"company_access_program_id","id"
        );
    }

    public function subEntities(): HasMany
    {
        return $this->hasMany(
            CompanyAccessProgramSubEntity::class,"company_access_program_id","id"
        );
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Deep relationship: Get all companies that have packages belonging to this CompanyAccessProgram
     */
    public function companiesWithPackages()
    {
        return $this->hasManyDeepFromRelations(
            $this->packages(),
            (new Package())->companies()
        );
    }

    /**
     * Deep relationship: Get all permissions through packages
     */
    public function permissionsThroughPackages()
    {
        return $this->hasManyDeepFromRelations(
            $this->packages(),
            (new Package())->permissions()
        );
    }

    /**
     * Alternative deep relationship using table names and foreign keys
     */
    public function packagesDeep()
    {
        return $this->hasManyDeep(
            Package::class,
            ['packages'], // intermediate tables (if any)
            ['company_access_program_id'], // foreign keys on intermediate tables
            ['id'] // local keys on intermediate tables
        );
    }
}
