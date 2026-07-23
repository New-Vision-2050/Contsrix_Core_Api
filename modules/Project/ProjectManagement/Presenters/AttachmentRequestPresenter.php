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
            'procedure_setting_id' => $this->request->procedure_setting_id,
            'status' => $this->request->status,
            'type' => $this->request->sender_company_id === tenant('id') ? 'outgoing' : 'incoming',
            'notes' => $this->request->notes,
            'created_at' => $this->request->created_at?->toISOString(),
            'responded_at' => $this->request->responded_at?->toISOString(),
        ];

            $data['project'] = $this->request->project ? [
                'id' => $this->request->project->id,
                'name' => $this->request->project->name,
                'serial_number' => $this->request->project->serial_number,
            ] : null;

            $projectProcedure = $this->request->projectProcedureSetting;

            $data['procedure_setting'] = $this->request->procedureSetting ? [
                'id' => $this->request->procedureSetting->id,
                'name' => $this->request->procedureSetting->name,
                'type' => $this->request->procedureSetting->type,
                'execute_type' => $this->request->procedureSetting->execute_type,
                'is_active' => (bool) $this->request->procedureSetting->is_active,
                'project_procedure_setting_id' => $projectProcedure?->id,
                'attachment_type' => $this->folderData($projectProcedure?->attachmentType),
                'attachment_sub_type' => $this->folderData($projectProcedure?->attachmentSubType),
                'attachment_sub_sub_type' => $this->folderData($projectProcedure?->attachmentSubSubType),
            ] : null;

            $data['sender_company'] = $this->request->senderCompany ? [
                'id' => $this->request->senderCompany->id,
                'name' => $this->request->senderCompany->name,
                'serial_number' => $this->request->senderCompany->serial_number,
            ] : null;

            $data['sender_company_name'] = $this->request->senderCompany
                ? $this->request->senderCompany->name
                : null;

            $data['receiver_company'] = $this->request->receiverCompany ? [
                'id' => $this->request->receiverCompany->id,
                'name' => $this->request->receiverCompany->name,
                'serial_number' => $this->request->receiverCompany->serial_number,
            ] : null;

            $data['receiver_company_name'] = $this->request->receiverCompany
                ? $this->request->receiverCompany->name
                : null;

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

                // Add attachments preview (simplified for quick view)
                $data['attachments_preview'] = $this->request->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'file_name' => $item->file_name,
                        'file_url' => $item->getFirstMediaUrl('attachments') ?: null,
                        'file_size' => $item->file_size,
                        'file_size_formatted' => $this->formatFileSize($item->file_size),
                        'file_type' => $item->file_type,
                        'status' => $item->status,
                    ];
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

            // Add request history from database
                $data['history'] = $this->request->history->map(function ($historyEntry) {
                    return [
                        'id' => $historyEntry->id,
                        'action' => $historyEntry->action,
                        'description' => $historyEntry->description,
                        'user' => $historyEntry->user ? [
                            'id' => $historyEntry->user->id,
                            'name' => $historyEntry->user->name,
                            'email' => $historyEntry->user->email,
                        ] : null,
                        'timestamp' => $historyEntry->created_at?->toISOString(),
                        'metadata' => $historyEntry->metadata,
                    ];
                })->toArray();


        return $data;
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    private function folderData($folder): ?array
    {
        if (!$folder) {
            return null;
        }

        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'project_id' => $folder->project_id,
        ];
    }
}
