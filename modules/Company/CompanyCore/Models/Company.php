<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
//use BasePackage\Shared\Traits\HasTranslations;

class Company extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    //use HasTranslations;
    // use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'user_name',
        'email',
        'phone',
        'country_id',
        'company_type_id',
        'company_field_id',
        'registration_type_id',
        'general_manager_id',
        'is_active',
        'complete_data',
        'date_activate',
        'registration_no',
        'serial_no',
        'image_path'
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
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }

}
