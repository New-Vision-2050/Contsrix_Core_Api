<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;

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
