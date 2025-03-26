<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;

// use BasePackage\Shared\Traits\HasTranslations;

class IdentifierSetting extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    // use SoftDeletes;

    public array $translatable = ["name"];

    public $incrementing = false;


    protected $fillable = [
        "status",
        "company_id"
    ];

    protected $casts = [
        'id' => 'string',
        "key" => "string"
    ];
}
