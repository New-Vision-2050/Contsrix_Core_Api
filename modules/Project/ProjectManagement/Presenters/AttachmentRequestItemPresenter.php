<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AttachmentRequestItemPresenter extends AbstractPresenter
{
    public function __construct(private AttachmentRequestItem $item)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->item->id,
            'attachment_request_id' => $this->item->attachment_request_id,
            'file_name' => $this->item->file_name,
            'file_path' => $this->item->file_path,
            'file_url' => $this->item->getFirstMediaUrl('attachments') ?: null,
            'file_type' => $this->item->file_type,
            'file_size' => $this->item->file_size,
            'file_size_formatted' => $this->formatFileSize($this->item->file_size),
            'status' => $this->item->status,
            'response_notes' => $this->item->response_notes,
            'responded_by' => $this->item->respondedByUser ? [
                'id' => $this->item->respondedByUser->id,
                'name' => $this->item->respondedByUser->name,
                'email' => $this->item->respondedByUser->email,
            ] : null,
            'responded_at' => $this->item->responded_at?->toISOString(),
            'created_at' => $this->item->created_at?->toISOString(),
        ];
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(?int $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
