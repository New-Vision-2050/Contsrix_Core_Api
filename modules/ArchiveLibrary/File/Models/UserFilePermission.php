<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ArchiveLibrary\File\Database\factories\FileFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ArchiveLibrary\Folder\Models\Folder;

//use BasePackage\Shared\Traits\HasTranslations;

class UserFilePermission extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'file_id',
        'permission_type'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

}
