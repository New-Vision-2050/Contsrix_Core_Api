<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\TypeAllowance\Database\factories\TypeAllowanceFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class TypeAllowance extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): TypeAllowanceFactory
    {
        return TypeAllowanceFactory::new();
    }
}
