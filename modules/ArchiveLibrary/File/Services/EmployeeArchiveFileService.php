<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;
use Modules\Shared\Media\Models\CustomMedia;

class EmployeeArchiveFileService
{
    public const TYPE_EMPLOYEE = 'employee';

    public const SECTION_PERSONAL = 'البيانات الشخصية';

    public const SECTION_ACADEMIC = 'البيانات الأكاديمية';

    public const SECTION_EMPLOYMENT = 'البيانات الوظيفية';

    public const SECTION_FINANCIAL = 'الامتيازات المالية';

    public const SUB_PERSONAL_PHOTO = 'الصورة الشخصية';

    public const SUB_RESIDENCE_INFO = 'معلومات الإقامة';

    public const SUB_QUALIFICATION = 'المؤهل';

    public const SUB_COURSES = 'الكورسات';

    public const SUB_PROFESSIONAL_CERTIFICATES = 'الشهادات المهنية';

    public const SUB_CV = 'السيرة الذاتية';

    public const SUB_EXPERIENCE = 'الخبرات';
    public const SUB_JOB_OFFER = 'عرض العمل';
    public const SUB_EMPLOYMENT_CONTRACT = 'العقد الوظيفي';
    public const SUB_SALARY = 'الراتب';
    public const SUB_BENEFITS = 'الامتيازات';

    public function __construct(
        private FolderRepository $folderRepository,
        private FileRepository $fileRepository,
    ) {
    }

    public function archiveUploadedFiles(
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        UploadedFile|array|null $files,
        string $mainSection,
        string $subSection,
        ?Model $sourceModel = null,
        mixed $sourceMedia = null,
    ): array {
        $uploadedFiles = $this->normalizeUploadedFiles($files);
        $mediaItems = $this->normalizeMediaItems($sourceMedia);

        $archiveFiles = [];

        foreach ($uploadedFiles as $index => $uploadedFile) {
            $archiveFile = $this->archiveUploadedFile(
                companyId: $companyId,
                employeeGlobalId: $employeeGlobalId,
                employeeName: $employeeName,
                file: $uploadedFile,
                mainSection: $mainSection,
                subSection: $subSection,
                sourceModel: $sourceModel,
                sourceMediaId: $mediaItems[$index]->id ?? null,
            );

            if ($archiveFile) {
                $archiveFiles[] = $archiveFile;
            }
        }

        return $archiveFiles;
    }

    /**
     * Mirror existing Employee Profile media into the employee archive without
     * duplicating archive rows on repeated sync runs.
     *
     * @return array<int, array{
     *     source_media_id: int|null,
     *     file_id: string|null,
     *     created: bool,
     *     updated: bool,
     *     attached: bool,
     *     skipped: bool,
     *     dry_run: bool
     * }>
     */
    public function syncExistingMedia(
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        mixed $sourceMedia,
        string $mainSection,
        string $subSection,
        ?Model $sourceModel = null,
        bool $dryRun = false,
    ): array {
        $mediaItems = $this->normalizeMediaItems($sourceMedia);

        $results = [];

        foreach ($mediaItems as $mediaItem) {
            if (! $mediaItem instanceof CustomMedia) {
                $results[] = $this->skippedResult(null, $dryRun);
                continue;
            }

            $results[] = $this->syncExistingMediaItem(
                companyId: $companyId,
                employeeGlobalId: $employeeGlobalId,
                employeeName: $employeeName,
                sourceMedia: $mediaItem,
                mainSection: $mainSection,
                subSection: $subSection,
                sourceModel: $sourceModel,
                dryRun: $dryRun,
            );
        }

        return $results;
    }

    public function archiveUploadedFile(
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        UploadedFile $file,
        string $mainSection,
        string $subSection,
        ?Model $sourceModel = null,
        ?int $sourceMediaId = null,
    ): ?File {
        $targetFolder = $this->resolveTargetFolder(
            companyId: $companyId,
            employeeGlobalId: $employeeGlobalId,
            employeeName: $employeeName,
            mainSection: $mainSection,
            subSection: $subSection,
        );

        return $this->fileRepository->createFile([
            'name' => $this->resolveFileName($file),
            'reference_number' => (string) Str::uuid(),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'folder_id' => $targetFolder->id,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'company_id' => $companyId,
            'type' => self::TYPE_EMPLOYEE,
            'employee_global_id' => $employeeGlobalId,
            'employee_section' => $mainSection,
            'employee_sub_section' => $subSection,
            'source_model_type' => $sourceModel ? $sourceModel::class : null,
            'source_model_id' => $sourceModel?->getKey(),
            'source_media_id' => $sourceMediaId,
        ], $file);
    }

    /**
     * @return array{
     *     source_media_id: int|null,
     *     file_id: string|null,
     *     created: bool,
     *     updated: bool,
     *     attached: bool,
     *     skipped: bool,
     *     dry_run: bool
     * }
     */
    private function syncExistingMediaItem(
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        CustomMedia $sourceMedia,
        string $mainSection,
        string $subSection,
        ?Model $sourceModel,
        bool $dryRun,
    ): array {
        $lookup = [
            'company_id' => $companyId,
            'type' => self::TYPE_EMPLOYEE,
            'employee_global_id' => $employeeGlobalId,
            'source_model_type' => $sourceModel ? $sourceModel::class : null,
            'source_model_id' => $sourceModel?->getKey(),
            'source_media_id' => $sourceMedia->id,
        ];

        $existingFile = File::query()
            ->withoutTenancy()
            ->where($lookup)
            ->first();

        if ($dryRun) {
            return [
                'source_media_id' => $sourceMedia->id,
                'file_id' => $existingFile?->id,
                'created' => $existingFile === null,
                'updated' => $existingFile ? $this->fileNeedsMetadataUpdate($existingFile, $mainSection, $subSection) : false,
                'attached' => $existingFile ? ! $this->archiveMediaExists($existingFile) : true,
                'skipped' => false,
                'dry_run' => true,
            ];
        }

        return DB::transaction(function () use (
            $companyId,
            $employeeGlobalId,
            $employeeName,
            $sourceMedia,
            $mainSection,
            $subSection,
            $sourceModel,
            $existingFile
        ): array {
            $targetFolder = $this->resolveTargetFolder(
                companyId: $companyId,
                employeeGlobalId: $employeeGlobalId,
                employeeName: $employeeName,
                mainSection: $mainSection,
                subSection: $subSection,
            );

            $data = [
                'name' => $this->resolveMediaFileName($sourceMedia),
                'folder_id' => $targetFolder->id,
                'project_id' => null,
                'access_type' => 'public',
                'status' => 1,
                'company_id' => $companyId,
                'type' => self::TYPE_EMPLOYEE,
                'employee_global_id' => $employeeGlobalId,
                'employee_section' => $mainSection,
                'employee_sub_section' => $subSection,
                'source_model_type' => $sourceModel ? $sourceModel::class : null,
                'source_model_id' => $sourceModel?->getKey(),
                'source_media_id' => $sourceMedia->id,
            ];

            if ($existingFile) {
                $existingFile->fill($data);
                $updated = $existingFile->isDirty();

                if ($updated) {
                    $existingFile->save();
                }

                $attached = $this->attachSourceMediaIfMissing($existingFile, $sourceMedia, $targetFolder->id);
                $mediaUpdated = $attached ? false : $this->syncArchiveMediaFolder($existingFile, $targetFolder->id);

                return [
                    'source_media_id' => $sourceMedia->id,
                    'file_id' => $existingFile->id,
                    'created' => false,
                    'updated' => $updated || $mediaUpdated,
                    'attached' => $attached,
                    'skipped' => false,
                    'dry_run' => false,
                ];
            }

            $file = File::query()
                ->withoutTenancy()
                ->create($data + [
                    'reference_number' => (string) Str::uuid(),
                    'start_date' => now()->toDateString(),
                    'end_date' => null,
                ]);

            $attached = $this->attachSourceMediaIfMissing($file, $sourceMedia, $targetFolder->id);

            return [
                'source_media_id' => $sourceMedia->id,
                'file_id' => $file->id,
                'created' => true,
                'updated' => false,
                'attached' => $attached,
                'skipped' => false,
                'dry_run' => false,
            ];
        });
    }

    private function fileNeedsMetadataUpdate(File $file, string $mainSection, string $subSection): bool
    {
        return $file->employee_section !== $mainSection
            || $file->employee_sub_section !== $subSection;
    }

    private function attachSourceMediaIfMissing(File $file, CustomMedia $sourceMedia, string $folderId): bool
    {
        if ($this->archiveMediaExists($file)) {
            return false;
        }

        $attributes = $this->cloneableMediaAttributes($sourceMedia);

        $archiveMedia = new CustomMedia;
        $archiveMedia->forceFill($attributes);
        $archiveMedia->uuid = (string) Str::uuid();
        $archiveMedia->model_id = $file->id;
        $archiveMedia->model_type = File::class;
        $archiveMedia->collection_name = 'upload';
        $archiveMedia->file_id = $file->id;
        $archiveMedia->folder_id = $folderId;
        $archiveMedia->setCustomProperty('file_id', $file->id);
        $archiveMedia->setCustomProperty('folder_id', $folderId);
        $archiveMedia->save();

        return true;
    }

    private function cloneableMediaAttributes(CustomMedia $sourceMedia): array
    {
        $attributes = collect($sourceMedia->getAttributes())
            ->except(['id', 'uuid', 'model_id', 'model_type', 'collection_name', 'file_id', 'folder_id', 'created_at', 'updated_at'])
            ->all();

        foreach (['manipulations', 'custom_properties', 'generated_conversions', 'responsive_images'] as $attribute) {
            $attributes[$attribute] = $this->arrayMediaAttribute($sourceMedia, $attribute, $attributes[$attribute] ?? []);
        }

        return $attributes;
    }

    private function arrayMediaAttribute(CustomMedia $sourceMedia, string $attribute, mixed $rawValue): array
    {
        $castValue = $sourceMedia->getAttribute($attribute);

        if (is_array($castValue)) {
            return $castValue;
        }

        if (is_array($rawValue)) {
            return $rawValue;
        }

        if (is_string($rawValue) && $rawValue !== '') {
            $decoded = json_decode($rawValue, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function syncArchiveMediaFolder(File $file, string $folderId): bool
    {
        $updated = false;

        CustomMedia::query()
            ->where(function ($query) use ($file) {
                $query->where(function ($query) use ($file) {
                    $query->where('model_type', File::class)
                        ->where('model_id', $file->id);
                })->orWhere('file_id', $file->id);
            })
            ->get()
            ->each(function (CustomMedia $media) use ($file, $folderId, &$updated) {
                $media->file_id = $file->id;
                $media->folder_id = $folderId;
                $media->setCustomProperty('file_id', $file->id);
                $media->setCustomProperty('folder_id', $folderId);

                if ($media->isDirty()) {
                    $media->save();
                    $updated = true;
                }
            });

        return $updated;
    }

    private function archiveMediaExists(File $file): bool
    {
        return $file->media()
            ->where('collection_name', 'upload')
            ->exists()
            || CustomMedia::query()
                ->where('file_id', $file->id)
                ->exists();
    }

    private function resolveTargetFolder(
        string $companyId,
        string $employeeGlobalId,
        string $employeeName,
        string $mainSection,
        string $subSection,
    ): Folder {
        $rootFolder = $this->folderRepository->findOrCreateEmployeeFolder(
            name: $employeeName,
            parentId: null,
            companyId: $companyId,
            employeeGlobalId: $employeeGlobalId,
            matchByName: false,
        );

        $mainSectionFolder = $this->folderRepository->findOrCreateEmployeeFolder(
            name: $mainSection,
            parentId: $rootFolder->id,
            companyId: $companyId,
            employeeGlobalId: $employeeGlobalId,
        );

        return $this->folderRepository->findOrCreateEmployeeFolder(
            name: $subSection,
            parentId: $mainSectionFolder->id,
            companyId: $companyId,
            employeeGlobalId: $employeeGlobalId,
        );
    }

    private function resolveFileName(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        return $name !== '' ? $name : 'employee-file';
    }

    private function resolveMediaFileName(CustomMedia $media): string
    {
        $name = (string) ($media->name ?: pathinfo((string) $media->file_name, PATHINFO_FILENAME));

        return $name !== '' ? $name : 'employee-file';
    }

    /**
     * @return array{
     *     source_media_id: int|null,
     *     file_id: string|null,
     *     created: bool,
     *     updated: bool,
     *     attached: bool,
     *     skipped: bool,
     *     dry_run: bool
     * }
     */
    private function skippedResult(?int $sourceMediaId, bool $dryRun): array
    {
        return [
            'source_media_id' => $sourceMediaId,
            'file_id' => null,
            'created' => false,
            'updated' => false,
            'attached' => false,
            'skipped' => true,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizeUploadedFiles(UploadedFile|array|null $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        $normalized = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $normalized[] = $file;
            } elseif (is_array($file)) {
                array_push($normalized, ...$this->normalizeUploadedFiles($file));
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeMediaItems(mixed $media): array
    {
        if ($media instanceof Collection) {
            return $media->values()->all();
        }

        if (is_array($media)) {
            return array_values($media);
        }

        return $media ? [$media] : [];
    }
}
