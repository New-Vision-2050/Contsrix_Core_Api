<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Models;

use App\Casts\UuidCast;
use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;
use OwenIt\Auditing\Contracts\Auditable;

class AdvanceRequest extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'advance_requests';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'company_id',
        'advance_policy_id',
        'amount',
        'installments',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'attachments',
        'notes',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'advance_policy_id' => UuidCast::class,
        'requested_by' => UuidCast::class,
        'approved_by' => UuidCast::class,
        'rejected_by' => UuidCast::class,
        'amount' => 'decimal:2',
        'installments' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function advancePolicy(): BelongsTo
    {
        return $this->belongsTo(AdvancePolicy::class, 'advance_policy_id');
    }

    /**
     * Workflow processes attached to this request (polymorphic).
     */
    public function processes(): MorphMany
    {
        return $this->morphMany(\Modules\Process\Models\Process::class, 'processable');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Workflow hook (ProcessWorkflowService): every process for this request
     * completed — final approval.
     */
    public function onAllProcessesCompleted(\Modules\Process\Models\Process $process): void
    {
        $lastActorId = $process->steps()
            ->whereNotNull('action_by')
            ->orderByDesc('acted_at')
            ->value('action_by');

        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $lastActorId,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();
    }

    /**
     * Workflow hook (ProcessWorkflowService): the process failed on rejection.
     * rejection_reason is set by the advance-request service before the step is
     * rejected, so it is preserved here.
     */
    public function onProcessFailed(\Modules\Process\Models\Process $process): void
    {
        $lastActorId = $process->steps()
            ->whereNotNull('action_by')
            ->orderByDesc('acted_at')
            ->value('action_by');

        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $lastActorId,
            'rejected_at' => now(),
        ])->save();
    }
}
