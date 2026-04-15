<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\AttachmentRequestRepository;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use Modules\Project\ProjectManagement\Models\AttachmentRequestHistory;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\File\Models\File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Ramsey\Uuid\Uuid;
use Modules\Project\ProjectManagement\Events\AttachmentRequestCreated;
use Modules\Project\ProjectManagement\Events\AttachmentRequestResponded;
use Modules\User\Models\User;

class AttachmentRequestService
{
    public function __construct(
        private AttachmentRequestRepository $repository,
        private FileUploadService $fileUploadService,
    ) {
    }

    /**
     * Create a new attachment request
     */
    public function createRequest(array $data): AttachmentRequest
    {
        // Verify project exists and is shared
        $project = ProjectManagement::findOrFail($data['project_id']);

        // Verify companies are involved in project sharing
        $this->verifyCompanyAccess($project, $data['receiver_company_id']);

        // Use provided serial number or auto-generate
        $serialNumber = $data['serial_number'] ?? $this->repository->generateSerialNumber();

        $requestData = [
            'serial_number' => $serialNumber,
            'name' => $data['name'],
            'date' => $data['date'],
            'project_id' => $data['project_id'],
            'sender_company_id' => (string) tenant('id'),
            'receiver_company_id' => $data['receiver_company_id'],
            'attachment_type_id' => $data['attachment_type_id'] ?? null,
            'attachment_sub_type_id' => $data['attachment_sub_type_id'] ?? null,
            'attachment_sub_sub_type_id' => $data['attachment_sub_sub_type_id'] ?? null,
            'status' => 'pending',
            'created_by_user_id' => (string) Auth::id(),
            'notes' => $data['notes'] ?? null,
        ];

        $items = $this->prepareAttachmentItems($data['attachments']);

        $request = $this->repository->createWithItems($requestData, $items);

        // Log history
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_created',
            description: 'Attachment request created',
            userId: (string) Auth::id(),
            metadata: [
                'request_name' => $request->name,
                'total_attachments' => count($items),
                'receiver_company' => $data['receiver_company_id'],
            ]
        );

        // Broadcast notification to receiver company users
        $this->broadcastToReceiverCompany($request);

        return $request;
    }

    /**
     * Get all requests (incoming and outgoing) for current company
     */
    public function getAllRequests(?string $projectId = null): Collection
    {
        return $this->repository->getAllRequests(tenant('id'), $projectId);
    }

    /**
     * Get outgoing requests for current company
     */
    public function getOutgoingRequests(?string $projectId = null): Collection
    {
        return $this->repository->getOutgoingRequests(tenant('id'), $projectId);
    }

    /**
     * Get incoming requests for current company
     */
    public function getIncomingRequests(?string $projectId = null): Collection
    {
        return $this->repository->getIncomingRequests(tenant('id'), $projectId);
    }

    /**
     * Get pending incoming requests
     */
    public function getPendingIncoming(?string $projectId = null): Collection
    {
        return $this->repository->getPendingIncoming(tenant('id'), $projectId);
    }

    /**
     * Get request by ID
     */
    public function getRequest(string $requestId): AttachmentRequest
    {
        $request = $this->repository->getWithItems($requestId);

        if (!$request) {
            throw new \Exception('Attachment request not found');
        }

        // Verify access
        if ($request->sender_company_id !== tenant('id') && $request->receiver_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized access to this request');
        }

        return $request;
    }

    /**
     * Respond to individual attachment item
     */
    public function respondToItem(string $itemId, string $action, ?string $notes = null): AttachmentRequestItem
    {
        $item = AttachmentRequestItem::with('attachmentRequest')->findOrFail($itemId);

        // Verify receiver company
        if ($item->attachmentRequest->receiver_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to respond to this item');
        }

        $userId = (string) Auth::id();

        $actionDescriptions = [
            'approve' => 'Attachment approved',
            'decline' => 'Attachment declined',
            'request_update' => 'Update requested for attachment',
        ];

        $actionKeys = [
            'approve' => 'attachment_approved',
            'decline' => 'attachment_declined',
            'request_update' => 'attachment_update_requested',
        ];

        switch ($action) {
            case 'approve':
                $item->approve($userId, $notes);
                // Save attachment to ArchiveLibrary folder
                $this->saveAttachmentToFolder($item);
                break;
            case 'decline':
                $item->decline($userId, $notes);
                break;
            case 'request_update':
                $item->requestUpdate($userId, $notes);
                break;
            default:
                throw new \Exception('Invalid action');
        }

        // Log history with detailed file information
        AttachmentRequestHistory::log(
            requestId: $item->attachment_request_id,
            action: $actionKeys[$action],
            description: $actionDescriptions[$action],
            userId: $userId,
            itemId: $item->id,
            metadata: [
                'item_id' => $item->id,
                'file_name' => $item->file_name,
                'file_path' => $item->file_path,
                'file_url' => $item->file_path ? asset('storage/' . $item->file_path) : null,
                'file_type' => $item->file_type,
                'file_size' => $item->file_size,
                'file_size_formatted' => $this->formatFileSize($item->file_size),
                'status' => $item->status,
                'response_notes' => $notes,
                'previous_status' => 'pending',
            ]
        );

        return $item->fresh(['respondedByUser', 'attachmentRequest']);
    }

    /**
     * Approve entire request
     */
    public function approveRequest(string $requestId): AttachmentRequest
    {
        $request = $this->getRequest($requestId);

        if ($request->receiver_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to approve this request');
        }

        $userId = (string) Auth::id();

        // Get all file details before approving
        $filesApproved = $request->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'file_name' => $item->file_name,
                'file_size' => $item->file_size,
                'file_size_formatted' => $this->formatFileSize($item->file_size),
                'file_type' => $item->file_type,
            ];
        })->toArray();

        $request->approveAll($userId);

        foreach (
            AttachmentRequestItem::with('attachmentRequest')
                ->where('attachment_request_id', $request->id)
                ->get() as $item
        ) {
            $this->saveAttachmentToFolder($item);
        }

        // Log history with all approved files
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_approved',
            description: 'Request fully approved - All attachments approved',
            userId: $userId,
            metadata: [
                'total_items' => $request->items->count(),
                'files_approved' => $filesApproved,
            ]
        );

        $request = $request->fresh(['items', 'respondedByUser']);

        // Broadcast notification to sender company users
        $this->broadcastToSenderCompany($request, 'approved');

        return $request;
    }

    /**
     * Decline entire request
     */
    public function declineRequest(string $requestId): AttachmentRequest
    {
        $request = $this->getRequest($requestId);

        if ($request->receiver_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to decline this request');
        }

        $userId = (string) Auth::id();

        // Get all file details before declining
        $filesDeclined = $request->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'file_name' => $item->file_name,
                'file_size' => $item->file_size,
                'file_size_formatted' => $this->formatFileSize($item->file_size),
                'file_type' => $item->file_type,
            ];
        })->toArray();

        $request->declineAll($userId);

        // Log history with all declined files
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_declined',
            description: 'Request declined - All attachments declined',
            userId: $userId,
            metadata: [
                'total_items' => $request->items->count(),
                'files_declined' => $filesDeclined,
            ]
        );

        $request = $request->fresh(['items', 'respondedByUser']);

        // Broadcast notification to sender company users
        $this->broadcastToSenderCompany($request, 'declined');

        return $request;
    }

    /**
     * Prepare attachment items from uploaded files
     */
    private function prepareAttachmentItems(array $attachments): array
    {
        $items = [];

        foreach ($attachments as $attachment) {
            $items[] = [
                'file_name' => $attachment->getClientOriginalName(),
                'file_path' => null, // Will be populated by media library
                'file_type' => $attachment->getClientMimeType(),
                'file_size' => $attachment->getSize(),
                'status' => 'pending',
                'uploaded_file' => $attachment, // Store for media library processing
            ];
        }

        return $items;
    }

    /**
     * Verify company has access to project
     */
    private function verifyCompanyAccess(ProjectManagement $project, string $companyId): void
    {
        // Check if project is owned or shared with the company
        $hasAccess = $project->company_id === $companyId ||
                     $project->shares()
                         ->where('shared_with_company_id', $companyId)
                         ->where('status', 'accepted')
                         ->exists();

        if (!$hasAccess) {
            throw new \Exception('Receiver company does not have access to this project');
        }
    }

    /**
     * Save approved attachment to ArchiveLibrary folder
     */
    private function saveAttachmentToFolder(AttachmentRequestItem $item): void
    {
        $request = $item->attachmentRequest;

        // Get or create folder structure
        $folderId = $this->getOrCreateFolderPath($request);

        if (!$folderId) {
            // If no folder structure, save to project root folder
            $folderId = $this->getProjectRootFolder($request->project_id);
        }

        // Get media items from the attachment request item
        $receiverTenantId = (string) tenant('id');
        $senderTenantId = (string) $request->sender_company_id;

        // Switch to sender tenant to get media
        $mediaItems = $this->getMediaFromSenderTenant($item, $senderTenantId, $receiverTenantId);

        if ($mediaItems->isEmpty()) {
            return;
        }

        // Create file record in receiver tenant
        $file = File::create([
            'name' => pathinfo($item->file_name, PATHINFO_FILENAME),
            'folder_id' => $folderId,
            'project_id' => $request->project_id,
            'company_id' => $receiverTenantId,
            'access_type' => 'public',
            'status' => 1,
        ]);

        // Replicate media items to the file (like legal data pattern)
        foreach ($mediaItems as $mediaItem) {
            $replicatedMedia = $mediaItem->replicate(['id', 'uuid']);
            $replicatedMedia->model_id = $file->id;
            $replicatedMedia->model_type = File::class;
            $replicatedMedia->collection_name= "upload";
            $replicatedMedia->save();
        }
    }

    /**
     * Get media items from sender tenant for the attachment request item
     */
    private function getMediaFromSenderTenant(
        AttachmentRequestItem $item,
        string $senderTenantId,
        string $receiverTenantId
    ): \Illuminate\Support\Collection {
        if ($senderTenantId === $receiverTenantId) {
            // Same tenant - get media directly
            return Media::where('model_id', Uuid::fromString($item->id))
                ->where('model_type', AttachmentRequestItem::class)
                ->get();
        }

        // Different tenant - switch context to get media
        tenancy()->end();
        tenancy()->initialize($senderTenantId);

        try {
            $mediaItems = Media::where('model_id', Uuid::fromString($item->id))
                ->where('model_type', AttachmentRequestItem::class)
                ->get();
        } finally {
            tenancy()->end();
            tenancy()->initialize($receiverTenantId);
        }

        return $mediaItems;
    }

    /**
     * Get or create folder path based on attachment types
     */
    private function getOrCreateFolderPath(AttachmentRequest $request): ?string
    {
        $projectFolder = $this->getProjectRootFolder($request->project_id);

        if (!$projectFolder) {
            return null;
        }

        $currentFolderId = $projectFolder;

        // attachment_type_id represents the first level folder
        if ($request->attachment_type_id) {
            $currentFolderId = $request->attachment_type_id;
        }

        // attachment_sub_type_id represents the second level (subfolder)
        if ($request->attachment_sub_type_id) {
            $currentFolderId = $request->attachment_sub_type_id;
        }

        // attachment_sub_sub_type_id represents the third level (sub-subfolder)
        if ($request->attachment_sub_sub_type_id) {
            $currentFolderId = $request->attachment_sub_sub_type_id;
        }

        // Verify folder exists
        $folder = Folder::where("id",$currentFolderId)->withoutTenancy()->first();

        return $folder ? $folder->id : $projectFolder;
    }

    /**
     * Get project root folder (folder_id = project_id)
     */
    private function getProjectRootFolder(string $projectId): ?string
    {
        $folder = Folder::where('id', $projectId)->withoutTenancy()
            ->whereNull('parent_id')
            ->first();

        return $folder?->id;
    }

    /**
     * Get folder children for dropdown selection
     */
    public function getFolderChildren(?string $parentId = null, ?string $projectId = null): Collection
    {
        $query = Folder::query()->withoutTenancy();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } elseif ($projectId) {
            // Get project root folders
            $query->where('project_id', $projectId)->whereNull('parent_id');
        } else {
            $query->whereNull('parent_id');
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'project_id']);
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

    /**
     * Broadcast notification to receiver company users when new request is created
     */
    private function broadcastToReceiverCompany(AttachmentRequest $request): void
    {
        // Get all users from receiver company
        $receiverCompanyUsers = User::where('company_id', $request->receiver_company_id)
            ->whereNotNull('id')
            ->get();

        // Count pending incoming requests for receiver company (including the new one)
        $pendingIncomingCount = AttachmentRequest::where('receiver_company_id', $request->receiver_company_id)
            ->whereIn('status', ['pending', 'semi-approved'])
            ->count();

        foreach ($receiverCompanyUsers as $user) {
            event(new AttachmentRequestCreated($request, $pendingIncomingCount));
        }
    }

    /**
     * Broadcast notification to sender company users when request is responded
     */
    private function broadcastToSenderCompany(AttachmentRequest $request, string $action): void
    {
        // Get all users from sender company
        $senderCompanyUsers = User::where('company_id', $request->sender_company_id)
            ->whereNotNull('id')
            ->get();

        foreach ($senderCompanyUsers as $user) {
            event(new AttachmentRequestResponded($request, (string) $user->id, $action));
        }
    }

    /**
     * Replace media in attachment request item
     */
    public function replaceMedia(string $itemId, UploadedFile $newFile): AttachmentRequestItem
    {
        $item = AttachmentRequestItem::with('attachmentRequest')->findOrFail($itemId);

        // Verify sender company (only sender can replace media)
//        if ($item->attachmentRequest->sender_company_id !== tenant('id')) {
//            throw new \Exception('Unauthorized to replace media for this item');
//        }

        // Verify item is pending or update_requested
        if (!in_array($item->status, ['pending', 'update_requested'])) {
            throw new \Exception('Can only replace media for pending or update requested items');
        }

        return DB::transaction(function () use ($item, $newFile) {
            // Clear existing media
            $item->clearMediaCollection('attachments');

            // Upload new file
            $this->fileUploadService->uploadFile(
                $item,
                $newFile,
                'attachment-requests/items',
                'attachments',
                'public'
            );

            // Update item file information
            $item->update([
                'file_name' => $newFile->getClientOriginalName(),
                'file_type' => $newFile->getClientMimeType(),
                'file_size' => $newFile->getSize(),
                'status' => 'pending', // Reset to pending after replacement
                'responded_by_user_id' => null,
                'responded_at' => null,
                'response_notes' => null,
            ]);

            // Log history
            AttachmentRequestHistory::log(
                requestId: $item->attachment_request_id,
                action: 'media_replaced',
                description: 'Media file replaced',
                userId: (string) Auth::id(),
                itemId: $item->id,
                metadata: [
                    'item_id' => $item->id,
                    'new_file_name' => $newFile->getClientOriginalName(),
                    'new_file_type' => $newFile->getClientMimeType(),
                    'new_file_size' => $newFile->getSize(),
                    'new_file_size_formatted' => $this->formatFileSize($newFile->getSize()),
                ]
            );

            // Update parent request status if needed
            $item->attachmentRequest->updateStatusBasedOnItems();

            return $item->fresh(['media', 'attachmentRequest']);
        });
    }
}
