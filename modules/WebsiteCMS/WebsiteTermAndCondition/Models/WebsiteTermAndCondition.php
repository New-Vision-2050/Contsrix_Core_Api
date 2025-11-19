<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Database\factories\WebsiteTermAndConditionFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

//use BasePackage\Shared\Traits\HasTranslations;

class WebsiteTermAndCondition extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'website_terms_and_conditions';

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'content',
        "company_id"
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): WebsiteTermAndConditionFactory
    {
        return WebsiteTermAndConditionFactory::new();
    }
}
