<?php

declare(strict_types=1);

namespace Modules\Shared\Notification\Services;

use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class NotificationCountService
{
    /**
     * Get pending notification counts for current company
     */
    public function getPendingCounts(): array
    {
        $companyId = tenant('id');

        // Count pending attachment requests (incoming)
        $pendingAttachmentRequests = AttachmentRequest::where('receiver_company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        // Count semi-approved attachment requests (partial approval)
        $semiApprovedAttachmentRequests = AttachmentRequest::where('receiver_company_id', $companyId)
            ->where('status', 'semi-approved')
            ->count();

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

        // Get pending attachment requests
        $attachmentRequests = AttachmentRequest::where('receiver_company_id', $companyId)
            ->whereIn('status', ['pending', 'semi-approved'])
            ->with(['senderCompany', 'project', 'createdByUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'type' => 'attachment_request',
                    'serial_number' => $request->serial_number,
                    'name' => $request->name,
                    'status' => $request->status,
                    'sender_company' => $request->senderCompany?->name,
                    'project' => $request->project?->name,
                    'created_by' => $request->createdByUser?->name,
                    'created_at' => $request->created_at?->toISOString(),
                ];
            });

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
