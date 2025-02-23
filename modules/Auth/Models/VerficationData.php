<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class VerficationData extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'data',
        'token',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
        "data"=>"array"
    ];
}
