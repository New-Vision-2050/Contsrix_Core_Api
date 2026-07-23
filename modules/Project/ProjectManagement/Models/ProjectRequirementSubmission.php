<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Process\Models\Process;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectRequirementSubmission extends Model implements HasMedia
{
    use InteractsWithMedia;
    use UuidTrait;

    public const PROCESSABLE_TYPE = 'project_requirement_submission';

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

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'processable_id')
            ->where('processable_type', self::PROCESSABLE_TYPE);
    }

    public function projectRequirementSubmissionProcess(): HasOne
    {
        return $this->hasOne(Process::class, 'processable_id')
            ->where('processable_type', self::PROCESSABLE_TYPE);
    }
}
