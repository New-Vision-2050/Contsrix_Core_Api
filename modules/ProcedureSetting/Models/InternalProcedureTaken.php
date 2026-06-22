<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class InternalProcedureTaken extends Model
{
    use UuidTrait;
    use BelongsToTenant;

    protected $table = 'internal_procedure_takens';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'processable_type',
        'processable_id',
        'procedure_setting_id',
        'form',
        'taken_by',
        'taken_at',
    ];

    protected $casts = [
        'id'         => 'string',
        'taken_at'   => 'datetime',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function processable(): MorphTo
    {
        return $this->morphTo();
    }

    public function procedureSetting(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id');
    }

    public function takenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }
}
