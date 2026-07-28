<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ArchiveLibrary\Folder\Database\factories\FolderFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\Company\CompanyCore\Models\Company;
//use BasePackage\Shared\Traits\HasTranslations;
use Modules\User\Models\User;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Folder extends Model implements HasMedia ,Auditable
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

    /**
     * Folders created and owned by the application itself. They mirror a
     * business record (e.g. an emergency work order) so their name and
     * position in the tree must stay in sync with that record.
     */
    public const TYPE_SYSTEM = 'system';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'parent_id',
        'project_id',
        'access_type',
        'status',
        'type',
        'employee_global_id',
        "password",
        "company_id"
    ];

    protected $casts = [
        'id' => 'string',
        'password' => 'hashed',
        'status' => 'integer',
    ];
//    protected $hidden = [
//        'password',
//    ];



    /**
     * Whether the folder is managed by the application and must not be
     * renamed, moved or deleted by users.
     */
    public function isSystemManaged(): bool
    {
        return $this->type === self::TYPE_SYSTEM
            || $this->name === config('folder.official_documents_name', 'المستندات الرسمية');
    }

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

    public function project()
    {
        return $this->belongsTo(\Modules\Project\ProjectManagement\Models\ProjectManagement::class, 'project_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }
    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }
}
