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
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Folder extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use BelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'parent_id',
        'access_type',
        "password",
        "company_id"
    ];

    protected $casts = [
        'id' => 'string',
        'password' => 'hashed',
    ];
//    protected $hidden = [
//        'password',
//    ];



    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }
    public function files()
    {
        return $this->hasMany(File::class);
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }

    public function users()
    {
        return $this->belongsToMany(User::class,"user_folder_permissions","folder_id","user_id");
    }
    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }
}
