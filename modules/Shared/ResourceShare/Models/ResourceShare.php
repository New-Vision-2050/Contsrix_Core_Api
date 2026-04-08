<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class ResourceShare extends Model
{
    use UuidTrait;

    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'owner_company_id',
        'shared_with_company_id',
        'status',
        'schema_ids',
        'shared_by_user_id',
        'responded_by_user_id',
        'responded_at',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'shareable_type' => 'string',
        'shareable_id' => 'string',
        'owner_company_id' => 'string',
        'shared_with_company_id' => 'string',
        'status' => 'string',
        'schema_ids' => 'array',
        'shared_by_user_id' => 'string',
        'responded_by_user_id' => 'string',
        'responded_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the shareable resource (polymorphic)
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the company that owns the resource
     */
    public function ownerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'owner_company_id');
    }

    /**
     * Get the company that resource is shared with
     */
    public function sharedWithCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'shared_with_company_id');
    }

    /**
     * Get the user who shared the resource
     */
    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    /**
     * Get the user who responded to the share
     */
    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    /**
     * Check if share is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if share is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if share is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Accept the share
     */
    public function accept(string $userId): bool
    {
        return $this->update([
            'status' => 'accepted',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject the share
     */
    public function reject(string $userId): bool
    {
        return $this->update([
            'status' => 'rejected',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }
}
