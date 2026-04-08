<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class AttachmentRequest extends Model
{
    use UuidTrait;

    protected $table = 'attachment_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'serial_number',
        'name',
        'date',
        'project_id',
        'sender_company_id',
        'receiver_company_id',
        'attachment_type_id',
        'attachment_sub_type_id',
        'attachment_sub_sub_type_id',
        'status',
        'created_by_user_id',
        'responded_by_user_id',
        'responded_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the project this request belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id');
    }

    /**
     * Get the company that sent the request
     */
    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sender_company_id');
    }

    /**
     * Get the company that receives the request
     */
    public function receiverCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'receiver_company_id');
    }

    /**
     * Get the user who created the request
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who responded to the request
     */
    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    /**
     * Get all attachment items for this request
     */
    public function items(): HasMany
    {
        return $this->hasMany(AttachmentRequestItem::class, 'attachment_request_id');
    }

    /**
     * Get all history entries for this request
     */
    public function history(): HasMany
    {
        return $this->hasMany(AttachmentRequestHistory::class, 'attachment_request_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Check if request is semi-approved
     */
    public function isSemiApproved(): bool
    {
        return $this->status === 'semi-approved';
    }

    /**
     * Update request status based on items
     */
    public function updateStatusBasedOnItems(): void
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            return;
        }

        $approvedCount = $items->where('status', 'approved')->count();
        $declinedCount = $items->where('status', 'declined')->count();
        $totalCount = $items->count();

        if ($approvedCount === $totalCount) {
            $this->update(['status' => 'approved']);
        } elseif ($declinedCount === $totalCount) {
            $this->update(['status' => 'declined']);
        } elseif ($approvedCount > 0 || $declinedCount > 0) {
            $this->update(['status' => 'semi-approved']);
        } else {
            $this->update(['status' => 'pending']);
        }
    }

    /**
     * Approve entire request and all items
     */
    public function approveAll(string $userId): bool
    {
        $this->items()->update([
            'status' => 'approved',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);

        return $this->update([
            'status' => 'approved',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }

    /**
     * Decline entire request and all items
     */
    public function declineAll(string $userId): bool
    {
        $this->items()->update([
            'status' => 'declined',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);

        return $this->update([
            'status' => 'declined',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }
}
