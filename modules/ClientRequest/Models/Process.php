<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ClientRequest\Enums\ProcessStatus;

class Process extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'processes';

    protected $fillable = [
        'client_request_id',
        'type',
        'execute_type',
        'status',
        'template_snapshot',
    ];

    protected $casts = [
        'id'                 => 'string',
        'client_request_id'  => 'string',
        'status'             => ProcessStatus::class,
        'template_snapshot'  => 'array',
    ];

    public function clientRequest(): BelongsTo
    {
        return $this->belongsTo(ClientRequest::class, 'client_request_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'process_id');
    }
}
