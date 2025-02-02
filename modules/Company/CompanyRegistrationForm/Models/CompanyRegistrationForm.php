<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyRegistrationForm\Database\factories\CompanyRegistrationFormFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class CompanyRegistrationForm extends Model
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
        'company_id',
        'registration_no'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): CompanyRegistrationFormFactory
    {
        return CompanyRegistrationFormFactory::new();
    }
}
