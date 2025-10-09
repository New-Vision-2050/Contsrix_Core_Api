<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ArchiveLibrary\File\Database\factories\FileFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\User\Models\User;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

//use BasePackage\Shared\Traits\HasTranslations;

class File extends Model implements HasMedia , Auditable
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use BelongsToTenant;
    use \OwenIt\Auditing\Auditable;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'reference_number',
        'start_date',
        'end_date',
        'folder_id',
        'access_type',
    ];

    protected $casts = [
        'id' => 'string',
        'reference_number' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    protected $appends = ['media_urls'];

    public function getMediaUrlsAttribute()
    {
        if (!$this->media) {
            return [];
        }
        return $this->media->map(fn($media) => $media->getFullUrl());
    }

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            \Modules\User\Models\User::class,
            'user_file_permissions',
            'file_id',
            'user_id'
        )->withPivot('folder_id', 'permission_type')->withTimestamps();
    }

    public function getRelationshipToPrimaryModel(): string
    {

        return "folder";
    }
    public function fileShare()
    {
        return $this->belongsToMany(User::class,"file_shares","file_id","user_id");
    }
}
