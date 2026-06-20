<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;

class Stakeholder extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'status' => 'integer',
    ];
}
