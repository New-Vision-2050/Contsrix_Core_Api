<?php

declare(strict_types=1);

namespace Modules\Shared\Notification\Services;

use Modules\Shared\ResourceShare\Models\ResourceShare;

class NotificationCountService
{
    /**
     * Get pending notification counts for current company
     */
    public function getPendingCounts(): array
    {
        $companyId = tenant('id');

        $pendingAttachmentRequests = 0;
        $semiApprovedAttachmentRequests = 0;

        // Count pending resource shares (incoming)
        $pendingResourceShares = ResourceShare::where('shared_with_company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        // Total pending notifications
        $totalPending = $pendingAttachmentRequests + $semiApprovedAttachmentRequests + $pendingResourceShares;

        return [
            'total_pending' => $totalPending,
            'pending_attachment_requests' => $pendingAttachmentRequests,
            'semi_approved_attachment_requests' => $semiApprovedAttachmentRequests,
            'pending_resource_shares' => $pendingResourceShares,
            'breakdown' => [
                'attachment_requests' => [
                    'pending' => $pendingAttachmentRequests,
                    'semi_approved' => $semiApprovedAttachmentRequests,
                    'total' => $pendingAttachmentRequests + $semiApprovedAttachmentRequests,
                ],
                'resource_shares' => [
                    'pending' => $pendingResourceShares,
                ],
            ],
        ];
    }

    /**
     * Get detailed pending notifications for current company
     */
    public function getPendingNotifications(): array
    {
        $companyId = tenant('id');

        $attachmentRequests = collect();

        // Get pending resource shares
        $resourceShares = ResourceShare::where('shared_with_company_id', $companyId)
            ->where('status', 'pending')
            ->with(['ownerCompany', 'sharedByUser', 'shareable'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($share) {
                $shareable = $share->shareable;
                $resourceName = $shareable ? ($shareable->name ?? $shareable->title ?? $shareable->serial_number ?? 'Unknown') : 'Unknown';

                return [
                    'id' => $share->id,
                    'type' => 'resource_share',
                    'shareable_type' => $share->shareable_type,
                    'resource_name' => $resourceName,
                    'status' => $share->status,
                    'owner_company' => $share->ownerCompany?->name,
                    'shared_by' => $share->sharedByUser?->name,
                    'notes' => $share->notes,
                    'created_at' => $share->created_at?->toISOString(),
                ];
            });

        return [
            'attachment_requests' => $attachmentRequests->toArray(),
            'resource_shares' => $resourceShares->toArray(),
            'total_count' => $attachmentRequests->count() + $resourceShares->count(),
        ];
    }
}
