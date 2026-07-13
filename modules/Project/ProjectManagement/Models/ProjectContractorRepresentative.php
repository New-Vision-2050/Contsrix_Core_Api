<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContractorRepresentative extends Model
{
    use UuidTrait;

    protected $table = 'project_contractor_representatives';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_contractor_id',
        'name',
        'mobile',
        'nationality',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function projectContractor(): BelongsTo
    {
        return $this->belongsTo(ProjectContractor::class, 'project_contractor_id')->withoutGlobalScopes();
    }
}
