<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class IdentifierSetting extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;


    protected $fillable = [
        'name',
        "default"
    ];

    protected $casts = [
        'id' => 'string',
    ];
}
