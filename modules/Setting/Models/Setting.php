<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    protected $fillable = [
        "key",
        "value"
    ];
}
