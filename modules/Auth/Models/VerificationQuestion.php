<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class VerificationQuestion extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'question',
        'answer',
    ];

    protected $casts = [
        'id' => 'string',
    ];
}
