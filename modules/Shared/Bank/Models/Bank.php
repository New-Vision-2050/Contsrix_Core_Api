<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\Bank\Database\factories\BankFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class Bank extends Model
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
        'country_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): BankFactory
    {
        return BankFactory::new();
    }
}
