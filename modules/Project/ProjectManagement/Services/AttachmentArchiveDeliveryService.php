<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;
use Ramsey\Uuid\Uuid;

/**
 * Delivers approved attachment media into the receiving company's ArchiveLibrary.
 * Shared by AttachmentRequest items and ProjectRequirementSubmission workflows.
 */
final class AttachmentArchiveDeliveryService
{
    public function deliverAttachmentRequestItem(AttachmentRequestItem $item): void
    {
        $request = $item->attachmentRequest;
        $request->loadMissing('projectProcedureSetting');

        $this->deliver(
            source: $item,
            sourceModelType: AttachmentRequestItem::class,
            collectionName: 'attachments',
            projectId: (string) $request->project_id,
            sourceCompanyId: (string) $request->sender_company_id,
            receiverCompanyId: (string) tenant('id'),
            fileName: (string) $item->file_name,
            attachmentTypeId: $request->attachmentTypeId(),
            attachmentSubTypeId: $request->attachmentSubTypeId(),
            attachmentSubSubTypeId: $request->attachmentSubSubTypeId(),
        );
    }

    public function deliverRequirementSubmission(
        ProjectRequirementSubmission $submission,
        ?string $uploaderCompanyId = null,
    ): void {
        $submission->loadMissing(['requirement', 'media']);
        $requirement = $submission->requirement;
        if ($requirement === null) {
            return;
        }

        $projectProcedure = ProjectProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('project_id', (string) $submission->project_id)
            ->where('procedure_setting_id', (string) $requirement->procedure_setting_id)
            ->first();

        // The submission media rows live in the uploader's tenant. When approving,
        // the caller passes the uploader company from the process metadata; on the
        // no-steps immediate path the uploader is simply the current tenant.
        $sourceCompanyId = $uploaderCompanyId ?? (string) tenant('id');
        $receiverCompanyId = (string) tenant('id');
        $firstMedia = $submission->getFirstMedia('files');

        $this->deliver(
            source: $submission,
            sourceModelType: ProjectRequirementSubmission::class,
            collectionName: 'files',
            projectId: (string) $submission->project_id,
            sourceCompanyId: $sourceCompanyId,
            receiverCompanyId: $receiverCompanyId,
            fileName: (string) ($firstMedia?->file_name ?: $firstMedia?->name ?: 'requirement-submission'),
            attachmentTypeId: $projectProcedure?->attachment_type_id,
            attachmentSubTypeId: $projectProcedure?->attachment_sub_type_id,
            attachmentSubSubTypeId: $projectProcedure?->attachment_sub_sub_type_id,
        );
    }

    /**
     * @param  class-string<Model>  $sourceModelType
     */
    public function deliver(
        Model $source,
        string $sourceModelType,
        string $collectionName,
        string $projectId,
        string $sourceCompanyId,
        string $receiverCompanyId,
        string $fileName,
        ?string $attachmentTypeId = null,
        ?string $attachmentSubTypeId = null,
        ?string $attachmentSubSubTypeId = null,
    ): void {
        $mediaItems = $this->getMediaFromSourceTenant(
            source: $source,
            sourceModelType: $sourceModelType,
            sourceCompanyId: $sourceCompanyId,
            receiverCompanyId: $receiverCompanyId,
        );

        if ($mediaItems->isEmpty() && method_exists($source, 'getMedia')) {
            $mediaItems = collect($source->getMedia($collectionName));
        }

        if ($mediaItems->isEmpty()) {
            return;
        }

        $this->deliverMediaRows(
            mediaItems: $mediaItems,
            source: $source,
            sourceModelType: $sourceModelType,
            projectId: $projectId,
            receiverCompanyId: $receiverCompanyId,
            fileName: $fileName,
            attachmentTypeId: $attachmentTypeId,
            attachmentSubTypeId: $attachmentSubTypeId,
            attachmentSubSubTypeId: $attachmentSubSubTypeId,
        );
    }

    /**
     * @param  Collection<int, CustomMedia>  $mediaItems
     */
    private function deliverMediaRows(
        Collection $mediaItems,
        Model $source,
        string $sourceModelType,
        string $projectId,
        string $receiverCompanyId,
        string $fileName,
        ?string $attachmentTypeId,
        ?string $attachmentSubTypeId,
        ?string $attachmentSubSubTypeId,
    ): void {
        $folderId = $this->resolveFolderId(
            $projectId,
            $attachmentTypeId,
            $attachmentSubTypeId,
            $attachmentSubSubTypeId,
        ) ?? $this->getProjectRootFolder($projectId);

        if ($folderId === null) {
            return;
        }

        // Prefer the real (source-tenant) media file name so cross-tenant delivery
        // does not fall back to a generic placeholder name.
        $firstMedia = $mediaItems->first();
        if (! $firstMedia instanceof CustomMedia) {
            return;
        }

        $resolvedName = (string) ($firstMedia->file_name ?: $firstMedia->name ?: $fileName);

        // Workflow completion handling may be retried. Store the source identity
        // on the archive record so each uploaded media row is delivered exactly
        // once per receiving company.
        $file = File::query()
            ->withoutTenancy()
            ->firstOrCreate([
                'company_id' => $receiverCompanyId,
                'source_model_type' => $sourceModelType,
                'source_model_id' => (string) $source->getKey(),
                'source_media_id' => $firstMedia->id,
            ], [
                'name' => pathinfo($resolvedName, PATHINFO_FILENAME) ?: $resolvedName,
                'folder_id' => $folderId,
                'project_id' => $projectId,
                'access_type' => 'public',
                'status' => 1,
            ]);

        if (! $file->wasRecentlyCreated) {
            return;
        }

        foreach ($mediaItems as $mediaItem) {
            $attrs = collect($mediaItem->getAttributes())
                ->except(['id', 'uuid'])
                ->all();

            $newMedia = new CustomMedia();
            $newMedia->forceFill($attrs);
            $newMedia->custom_properties = is_string($attrs['custom_properties'] ?? null)
                ? json_decode($attrs['custom_properties'], true) ?? []
                : ($attrs['custom_properties'] ?? []);
            $newMedia->uuid = Uuid::uuid4()->toString();
            $newMedia->model_id = $file->id;
            $newMedia->model_type = File::class;
            $newMedia->collection_name = 'upload';
            $newMedia->file_id = $file->id;
            $newMedia->folder_id = $folderId;
            $newMedia->setCustomProperty('file_id', $file->id);
            $newMedia->setCustomProperty('folder_id', $folderId);
            $newMedia->save();

            app(PCloudArchiveSyncService::class)->dispatchSync($newMedia);
        }
    }

    /**
     * @param  class-string<Model>  $sourceModelType
     * @return Collection<int, CustomMedia>
     */
    private function getMediaFromSourceTenant(
        Model $source,
        string $sourceModelType,
        string $sourceCompanyId,
        string $receiverCompanyId,
    ): Collection {
        $modelId = (string) $source->getKey();

        if ($sourceCompanyId === $receiverCompanyId) {
            return CustomMedia::query()
                ->where('model_id', Uuid::fromString($modelId))
                ->where('model_type', $sourceModelType)
                ->get();
        }

        tenancy()->end();
        tenancy()->initialize($sourceCompanyId);

        try {
            return CustomMedia::query()
                ->where('model_id', Uuid::fromString($modelId))
                ->where('model_type', $sourceModelType)
                ->get();
        } finally {
            tenancy()->end();
            tenancy()->initialize($receiverCompanyId);
        }
    }

    private function resolveFolderId(
        string $projectId,
        ?string $attachmentTypeId,
        ?string $attachmentSubTypeId,
        ?string $attachmentSubSubTypeId,
    ): ?string {
        $projectFolder = $this->getProjectRootFolder($projectId);
        if ($projectFolder === null) {
            return null;
        }

        $currentFolderId = $projectFolder;

        if ($attachmentTypeId) {
            $currentFolderId = $attachmentTypeId;
        }
        if ($attachmentSubTypeId) {
            $currentFolderId = $attachmentSubTypeId;
        }
        if ($attachmentSubSubTypeId) {
            $currentFolderId = $attachmentSubSubTypeId;
        }

        $folder = Folder::query()->withoutTenancy()->where('id', $currentFolderId)->first();

        return $folder ? (string) $folder->id : $projectFolder;
    }

    private function getProjectRootFolder(string $projectId): ?string
    {
        $folder = Folder::query()
            ->withoutTenancy()
            ->where('id', $projectId)
            ->whereNull('parent_id')
            ->first();

        return $folder?->id !== null ? (string) $folder->id : null;
    }
}
