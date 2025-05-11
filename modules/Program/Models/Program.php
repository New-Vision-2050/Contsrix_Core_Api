<?php

declare(strict_types=1);

namespace Modules\Program\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Program\Database\factories\ProgramFactory;
use BasePackage\Shared\Traits\BaseFilterable;

class Program extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    protected $table = 'programs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
