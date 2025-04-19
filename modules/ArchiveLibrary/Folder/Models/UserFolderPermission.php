<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ArchiveLibrary\Folder\Database\factories\FolderFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ArchiveLibrary\File\Models\File;
//use BasePackage\Shared\Traits\HasTranslations;

class UserFolderPermission extends Model
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
        'folder_id',
        'permission_type'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }
}
