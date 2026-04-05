<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\ArchiveLibrary\File\Database\factories\FileFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\User\Models\User;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Shared\Media\Models\CustomMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

//use BasePackage\Shared\Traits\HasTranslations;

class File extends Model implements HasMedia , Auditable
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use BelongsToTenant;
    use \OwenIt\Auditing\Auditable;
    use HasRelationships;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $with = ["media","mediaFile"];

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'reference_number',
        'start_date',
        'end_date',
        'folder_id',
        'project_id',
        'access_type',
        'status',
        "management_hierarchy_id",
        "company_id"
    ];

    protected $casts = [
        'id' => 'string',
        'reference_number' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
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


    public function mediaFile()
    {
       return $this->hasOne(CustomMedia::class , "file_id");
    }



    /**
     * Get the first morphed model (if using single media file)
     *
     * Usage: $file->mediaFile->modelable or $file->getFirstMorphedModel()
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getFirstMorphedModel()
    {
        return $this->mediaFile?->modelable;
    }

    /**
     * Get models that are related through media (polymorphic relationship)
     *
     * Usage: $file->relatedThroughMedia(User::class)->get()
     *
     * @param string $relatedModel The model class you want to access (e.g., User::class)
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function relatedThroughMedia(string $relatedModel)
    {
        return $this->hasManyDeep(
            $relatedModel,
            [CustomMedia::class],
            ['file_id', 'model_id'],  // Foreign keys: media.file_id, target.id (matches media.model_id)
            ['id', 'id']               // Local keys: files.id, media.id
        )->where('media.model_type', $relatedModel);
    }

    /**
     * Replace the first media file with a new one without explicit deletion
     *
     * This updates the existing media record's file content and metadata
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $collectionName
     * @return \Modules\Shared\Media\Models\CustomMedia|null
     */
    public function replaceMedia($uploadedFile, string $collectionName = 'default')
    {
        $existingMedia = $this->getFirstMedia($collectionName);

        if (!$existingMedia) {
            // No existing media, just add new one
            return $this->addMedia($uploadedFile)
                ->toMediaCollection($collectionName);
        }

        // Store new file in same location (overwrites)
        $disk = $existingMedia->disk;
        $directory = dirname($existingMedia->getPath());
        $fileName = $existingMedia->file_name;

        // Save new file with same filename (overwrites old file)
        \Storage::disk($disk)->put(
            str_replace(storage_path('app/public/'), '', $existingMedia->getPath()),
            file_get_contents($uploadedFile->getRealPath())
        );

        // Update media metadata
        $existingMedia->update([
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'name' => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
        ]);

        return $existingMedia->fresh();
    }

    /**
     * Get all users who have marked this file as favourite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favouritedByUsers()
    {
        return $this->belongsToMany(
            \Modules\User\Models\User::class,
            'users_file_favourites',
            'file_id',
            'user_id'
        )->withTimestamps();
    }

    public function project()
    {
        return $this->belongsTo(\Modules\Project\ProjectManagement\Models\ProjectManagement::class, 'project_id');
    }

}
