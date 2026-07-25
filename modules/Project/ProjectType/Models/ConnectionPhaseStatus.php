<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionPhaseStatus extends Model
{
    protected $table = 'connection_phase_statuses';

    protected $fillable = [
        'connection_completion_phase_id',
        'name',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ConnectionCompletionPhase::class, 'connection_completion_phase_id');
    }
}
