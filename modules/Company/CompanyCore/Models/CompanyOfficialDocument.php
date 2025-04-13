<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

// use BasePackage\Shared\Traits\HasTranslations;

class CompanyOfficialDocument extends Model implements HasMedia
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToPrimaryModel;
    use InteractsWithMedia;

    // use HasTranslations;
    // use SoftDeletes;
    // protected $dates = ['deleted_at'];


    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'company_id',
        'document_type_id',
        'document_number',
        'start_date',
        'end_date',
        'notification_date'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }
}
