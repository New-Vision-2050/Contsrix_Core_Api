<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttachmentRequestItem extends Model implements HasMedia
{
    use UuidTrait, InteractsWithMedia;

    protected $table = 'attachment_request_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'attachment_request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'status',
        'responded_by_user_id',
        'responded_at',
        'response_notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the attachment request this item belongs to
     */
    public function attachmentRequest(): BelongsTo
    {
        return $this->belongsTo(AttachmentRequest::class, 'attachment_request_id');
    }

    /**
     * Get the user who responded to this item
     */
    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id')->withoutGlobalScopes();
    }

    /**
     * Check if item is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if item is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if item is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Check if update is requested
     */
    public function isUpdateRequested(): bool
    {
        return $this->status === 'update_requested';
    }

    /**
     * Approve this item
     */
    public function approve(string $userId, ?string $notes = null): bool
    {
        $result = $this->update([
            'status' => 'approved',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
            'response_notes' => $notes,
        ]);

        // Update parent request status
        $this->attachmentRequest->updateStatusBasedOnItems();

        return $result;
    }

    /**
     * Decline this item
     */
    public function decline(string $userId, ?string $notes = null): bool
    {
        $result = $this->update([
            'status' => 'declined',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
            'response_notes' => $notes,
        ]);

        // Update parent request status
        $this->attachmentRequest->updateStatusBasedOnItems();

        return $result;
    }

    /**
     * Request update for this item
     */
    public function requestUpdate(string $userId, ?string $notes = null): bool
    {
        $result = $this->update([
            'status' => 'update_requested',
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
            'response_notes' => $notes,
        ]);

        // Update parent request status
        $this->attachmentRequest->updateStatusBasedOnItems();

        return $result;
    }
}
