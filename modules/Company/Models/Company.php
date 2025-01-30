<?php

declare(strict_types=1);

namespace Modules\Company\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;

//use BasePackage\Shared\Traits\HasTranslations;

class Company extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country_id',
        'company_type_id',
        'company_field_id',
        'registration_type_id',
        'general_manager_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function generalManager()
    {
        return $this->belongsTo(User::class, 'general_manager_id', 'id');
    }

    public function companyType()
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function companyField()
    {
        return $this->belongsTo(CompanyField::class);
    }

    public function companyRegistrationType()
    {
        return $this->belongsTo(CompanyRegistrationType::class,'registration_type_id');
    }
    public function companyRegistrationForm()
    {
        return $this->hasOne(CompanyRegistrationForm::class);
    }
}
