<?php

declare(strict_types=1);

namespace Modules\SubEntity\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class RegistrationForm extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
    ];
}
