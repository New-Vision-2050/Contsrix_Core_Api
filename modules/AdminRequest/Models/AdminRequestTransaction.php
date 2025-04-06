<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class AdminRequestTransaction extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "admin_request_id",
        "requestable_id",
        "requestable_type",
        "action",
        "data",
        "status",
    ];

    public function requestable()
    {
        return $this->morphTo();
    }


    protected $casts = [
        'id' => 'string',
        "data" => "array"
    ];
}
