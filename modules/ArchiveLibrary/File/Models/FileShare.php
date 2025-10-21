<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class FileShare extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;
    protected $table = 'file_shares';

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'file_id',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];
}
