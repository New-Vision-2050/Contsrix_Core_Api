<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AttachmentRequestPresenter extends AbstractPresenter
{
    public function __construct(private AttachmentRequest $request)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->request->id,
            'serial_number' => $this->request->serial_number,
            'name' => $this->request->name,
            'date' => $this->request->date?->toDateString(),
            'project_id' => $this->request->project_id,
            'status' => $this->request->status,
            'attachment_type_id' => $this->request->attachment_type_id,
            'attachment_sub_type_id' => $this->request->attachment_sub_type_id,
            'attachment_sub_sub_type_id' => $this->request->attachment_sub_sub_type_id,
            'notes' => $this->request->notes,
            'created_at' => $this->request->created_at?->toISOString(),
            'responded_at' => $this->request->responded_at?->toISOString(),
        ];

        if (!$isListing) {
            $data['project'] = $this->request->project ? [
                'id' => $this->request->project->id,
                'name' => $this->request->project->name,
                'serial_number' => $this->request->project->serial_number,
            ] : null;

            $data['sender_company'] = $this->request->senderCompany ? [
                'id' => $this->request->senderCompany->id,
                'name' => $this->request->senderCompany->name,
                'serial_number' => $this->request->senderCompany->serial_number,
            ] : null;

            $data['receiver_company'] = $this->request->receiverCompany ? [
                'id' => $this->request->receiverCompany->id,
                'name' => $this->request->receiverCompany->name,
                'serial_number' => $this->request->receiverCompany->serial_number,
            ] : null;

            $data['created_by'] = $this->request->createdByUser ? [
                'id' => $this->request->createdByUser->id,
                'name' => $this->request->createdByUser->name,
                'email' => $this->request->createdByUser->email,
            ] : null;

            $data['responded_by'] = $this->request->respondedByUser ? [
                'id' => $this->request->respondedByUser->id,
                'name' => $this->request->respondedByUser->name,
                'email' => $this->request->respondedByUser->email,
            ] : null;

            // Include items
            if ($this->request->relationLoaded('items')) {
                $data['items'] = $this->request->items->map(function ($item) {
                    return (new AttachmentRequestItemPresenter($item))->getData();
                })->toArray();

                // Add statistics
                $totalItems = $this->request->items->count();
                $approvedItems = $this->request->items->where('status', 'approved')->count();
                $declinedItems = $this->request->items->where('status', 'declined')->count();
                $pendingItems = $this->request->items->where('status', 'pending')->count();
                $updateRequestedItems = $this->request->items->where('status', 'update_requested')->count();

                $data['statistics'] = [
                    'total_items' => $totalItems,
                    'approved_items' => $approvedItems,
                    'declined_items' => $declinedItems,
                    'pending_items' => $pendingItems,
                    'update_requested_items' => $updateRequestedItems,
                ];
            }
        }

        return $data;
    }
}
