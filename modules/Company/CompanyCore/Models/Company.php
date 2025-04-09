<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\AdminRequest\Models\AdminRequest;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Company extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use HasTranslations;

    // use SoftDeletes;

    public array $translatable = ["name"];

    protected $with = ['country', 'companyType', 'companyField', 'companyRegistrationType', 'generalManager',"mainBranch"];

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
        'registration_no_start_date',
        'registration_no_end_date',
        'serial_no',
        'image_path'
    ];
    protected $casts = [
        'id' => 'string',
        'date_activate' => 'date'
    ];
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
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
        return $this->belongsTo(CompanyRegistrationType::class, 'registration_type_id');
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }

    public function adminRequestTransaction()
    {
        return $this->morphMany(AdminRequest::class, 'requestable');
    }

    public function mainBranch()
    {
        return $this->hasOne(ManagementHierarchy::class, 'company_id')->where('parent_id', null)->where('type', 'branch');
    }

    public function companyAddress()
    {
        return $this->hasOne(CompanyAddress::class, 'company_id');
    }

}
