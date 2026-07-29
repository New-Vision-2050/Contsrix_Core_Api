<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ProjectOrderPermitNoteLog extends Model
{
    public const TYPE_PERMIT_TO_DEPARTMENTS = 'permit_to_departments';
    public const TYPE_DEPARTMENTS_TO_PERMIT = 'departments_to_permit';

    protected $table = 'project_order_permit_note_logs';

    protected $fillable = [
        'project_order_permit_id',
        'user_id',
        'note',
        'type',
        'timezone',
        'created_by_name',
    ];

    public function projectOrderPermit(): BelongsTo
    {
        return $this->belongsTo(ProjectOrderPermit::class, 'project_order_permit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
