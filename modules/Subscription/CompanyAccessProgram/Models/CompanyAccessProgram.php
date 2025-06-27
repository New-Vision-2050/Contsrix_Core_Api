<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Models;

use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Subscription\Module\Models\Module;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Subscription\CompanyAccessProgram\Database\factories\CompanyAccessProgramFactory;

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

    protected static function newFactory(): CompanyAccessProgramFactory
    {
        return CompanyAccessProgramFactory::new();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'company_access_program_module',
            'company_access_program_id',
            'module_id'
        );
    }

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
}
