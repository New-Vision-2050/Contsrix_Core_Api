<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;

// use BasePackage\Shared\Traits\HasTranslations;

class QuestionSetting extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;



    // use SoftDeletes;

    public array $translatable = ["question"];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
    ];

    protected $casts = [
        'id' => 'string',
    ];


}
