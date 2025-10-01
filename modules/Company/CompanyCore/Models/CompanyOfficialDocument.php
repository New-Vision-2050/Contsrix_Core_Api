<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\DocumentType\Models\DocumentType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

// use BasePackage\Shared\Traits\HasTranslations;

class CompanyOfficialDocument extends Model implements HasMedia , Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToPrimaryModel;
    use InteractsWithMedia;
    use OwenIt\Auditing\Contracts\Auditable;

    // use HasTranslations;
    // use SoftDeletes;
    // protected $dates = ['deleted_at'];


    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';
    protected $with = ["media"];

    protected $fillable = [
        'name',
        'description',
        'company_id',
        'document_type_id',
        'document_number',
        'start_date',
        'end_date',
        'notification_date',
        "management_hierarchy_id",
        "company_legal_data_id"
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(ManagementHierarchy::class,"management_hierarchy_id","id");
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }

    public function documentType()
    {
    return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, "requestable");
    }
    public function  companyLegalData()
    {
        return $this->belongsTo(CompanyLegalData::class);
    }

}
