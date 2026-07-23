<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectRequirementSubmission extends Model implements HasMedia
{
    use InteractsWithMedia;
    use UuidTrait;

    protected $table = 'project_requirement_submissions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'project_requirement_id',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'project_requirement_id' => 'string',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ProjectRequirement::class, 'project_requirement_id')->withoutGlobalScopes();
    }

}
