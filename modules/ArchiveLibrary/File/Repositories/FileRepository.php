<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Modules\ArchiveLibrary\File\Models\UserFilePermission;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;
use Ramsey\Uuid\UuidInterface;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;
use Spatie\Permission\Exceptions\UnauthorizedException;

/**
 * @property File $model
 * @method File findOneOrFail($id)
 * @method File findOneByOrFail(array $data)
 */
class FileRepository extends BaseRepository
{
    public function __construct(
        File $model,
        private FileUploadService $fileUploadService,
        private PermissionRepository $permissionRepository,
        private CompanyPermissionLimitRepository $companyPermissionLimitRepository
    ) {
        parent::__construct($model);
    }

    public function getFileList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFile(UuidInterface $id): File
    {
        return $this->model->withoutTenancy()->where([
            'id' => $id->toString(),
        ])->first();
    }

    public function createFile(array $data, UploadedFile $file): File
    {
        try {
            DB::beginTransaction();

            $fileModel = $this->create($data);
            $this->fileUploadService->uploadFile($fileModel, $file, "files", "upload", "public");

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());
        }
        return $fileModel;
    }

    public function updateFile(UuidInterface $id, array $data, $file): bool
    {
        try {
            DB::beginTransaction();
            $fileModel = $this->getFile($id);

            if ($fileModel->management_hierarchy_id != null) {
                throw new CustomException("validation.update-not-successful");

            }
            $updated = $this->update($id, $data);
            if ($file) {
                // Check storage limit BEFORE uploading new file
                $this->checkStorageLimitForUpdate($fileModel, $file);

                $fileModel->clearMediaCollection('upload');
                $this->fileUploadService->uploadFile($fileModel, $file, "files", "upload", "public");
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());

        }
        return $updated;
    }

    public function deleteFile(UuidInterface $id): bool
    {
        $fileModel = $this->getFile($id);
        if ($fileModel->management_hierarchy_id != null) {
            throw new CustomException("validation.delete-not-successful");

        } else {

            return $this->delete($id);

        }

    }

    public function attachUsers(File $file, array $userIds): void
    {
        foreach ($userIds as $userId) {
            UserFilePermission::create([
                'folder_id' => $file->folder_id ?? '',
                "user_id" => $userId,
                "file_id" => $file->id,
                'permission_type' => 'view',
            ]);
        }
    }

    public function syncUsers(File $file, array $userIds): void
    {
        $syncData = [];
        foreach ($userIds as $userId) {
            $syncData[$userId] = [
                'folder_id' => $file->folder_id ?? '',
                'permission_type' => 'view',
            ];
        }
        $file->users()->sync($syncData);
    }

    public function getFilesByFolder(?string $folderId): array
    {
        $query = $this->model->query();

        if ($folderId === null) {
            $query->whereNull('folder_id');
        } else {
            $query->where('folder_id', $folderId);
        }


        return [
            'data' => $query->get(),
            "count" => $query->count()
        ];
    }

    public function getTotalFilesCount(): int
    {
        return $this->model->query()->count();
    }

    public function getExpiredFilesCount($folderId): int
    {
        return $this->model->query()
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->where('folder_id', $folderId)
            ->count();
    }

    public function getValidFilesCount($folderId): int
    {
        return $this->model->query()
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->where('folder_id', $folderId)
            ->count();
    }

    public function getAlmostExpiredFiles($folderId): Collection
    {
        $threeDaysFromNow = now()->addDays(3);

        return $this->model->query()
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', $threeDaysFromNow)
            ->where('folder_id', $folderId)
            ->get();
    }

    public function getAlmostExpiredFilesCount($folderId): int
    {
        return $this->getAlmostExpiredFiles($folderId)
            ->count();
    }

    public function copyFile(array $fileIds, ?UuidInterface $targetFolderId): array
    {
//        try {
        DB::beginTransaction();

        $copiedFiles = [];

        foreach ($fileIds as $fileId) {
            $originalFile = $this->getFile(\Ramsey\Uuid\Uuid::fromString($fileId));
            $media = $originalFile->getFirstMedia('upload');

            if ($media) {
                $url = $media->getFullUrl();
                if (!str_starts_with($url, 'http')) {
                    $url = 'https://' . ltrim($url, '/');
                }



                $tempPath = tempnam(sys_get_temp_dir(), 'media_');
                file_put_contents($tempPath, Http::get($url)->body());

                $uploadedFile = new UploadedFile(
                    $tempPath,
                    $media->file_name,
                    $media->mime_type,
                    null,
                    true // bypass validation
                );
            }

            request()->files->set('file', $uploadedFile);

            // Create a copy of the file
            $copiedFile = $this->create([
                'name' => $originalFile->name . ' (Copy)',
                'reference_number' => $originalFile->reference_number,
                'start_date' => $originalFile->start_date,
                'end_date' => $originalFile->end_date,
                'folder_id' => $targetFolderId?->toString(),
                'access_type' => $originalFile->access_type,
            ]);

            // Copy media files
            foreach ($originalFile->getMedia('upload') as $media) {
                $newMedia = $media->replicate();

                $newMedia->model_id = $copiedFile->id;


                $newMedia->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

                $newMedia->save();
            }

            // Copy user permissions
            $userIds = $originalFile->users->pluck('id')->toArray();
            if (!empty($userIds)) {
                $this->attachUsers($copiedFile, $userIds);
            }

            $copiedFiles[] = $copiedFile->fresh();
        }

        DB::commit();

        return $copiedFiles;
//        } catch (\Exception $exception) {
//            DB::rollBack();
//            throw new CustomException($exception->getMessage());
//        }
    }

    public function cutFile(array $fileIds, ?UuidInterface $targetFolderId): array
    {
        try {
            DB::beginTransaction();

            $movedFiles = [];

            foreach ($fileIds as $fileId) {
                $fileUuid = \Ramsey\Uuid\Uuid::fromString($fileId);
                $file = $this->getFile($fileUuid);

                // Update folder_id to move the file
                $this->update($fileUuid, [
                    'folder_id' => $targetFolderId?->toString(),
                ]);

                // Update user permissions with new folder_id
                UserFilePermission::where('file_id', $fileId)
                    ->update(['folder_id' => $targetFolderId?->toString()]);

                $movedFiles[] = $this->getFile($fileUuid);
            }

            DB::commit();

            return $movedFiles;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());
        }
    }

    public function shareFile(array $fileIds, array $userIds): array
    {
        try {
            DB::beginTransaction();

            $sharedFiles = [];
            $allNewUserIds = [];
            $allExistingUserIds = [];

            foreach ($fileIds as $fileId) {
                $file = $this->getFile(\Ramsey\Uuid\Uuid::fromString($fileId));

                // Get existing user IDs before sync
                $existingUserIds = $file->fileShare()->pluck('user_id')->toArray();

                // Sync users in file_shares table
                $file->fileShare()->sync($userIds);

                // Determine newly added users
                $newUserIds = array_diff($userIds, $existingUserIds);

                $sharedFiles[] = $file->fresh();
                $allNewUserIds = array_unique(array_merge($allNewUserIds, array_values($newUserIds)));
                $allExistingUserIds = array_unique(array_merge($allExistingUserIds, $existingUserIds));
            }

            DB::commit();

            return [
                'files' => $sharedFiles,
                'new_user_ids' => array_values($allNewUserIds),
                'existing_user_ids' => array_values($allExistingUserIds),
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());
        }
    }

    /**
     * Get files for export
     *
     * @param array $filters Array of filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['ids']) && is_array($filters['ids']) && count($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['folder_id'])) {
            if ($filters['folder_id'] === 'null' || $filters['folder_id'] === null) {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $filters['folder_id']);
            }
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('reference_number', 'LIKE', "%{$search}%");
            });
        }

        // Include the folder and users relationships for export
        return $query->with(['folder', 'users'])->get();
    }

    public function getLimitSize()
    {
        return CompanyPermissionLimit::where([
            'company_id' => tenant("id"),
        ])->whereHas("permission", function ($q) {
            $q->where("name", "archive-library.archive-library*file.create");
        })->first();
    }

    /**
     * Check storage limit before updating file with new upload
     */
    private function checkStorageLimitForUpdate(File $fileModel, $uploadedFile): void
    {
        // Calculate new file size
        $newFileSize = 0;
        if (is_array($uploadedFile)) {
            foreach ($uploadedFile as $file) {
                if ($file && $file->isValid()) {
                    $newFileSize += round($file->getSize() / (1024 * 1024), 2);
                }
            }
        } else {
            if ($uploadedFile && $uploadedFile->isValid()) {
                $newFileSize = round($uploadedFile->getSize() / (1024 * 1024), 2);
            }
        }

        // Get old file size
        $oldFileSize = 0;
        $media = $fileModel->getFirstMedia("upload");
        if ($media && $media->size) {
            $oldFileSize = round($media->size / (1024 * 1024), 2);
        }

        // Calculate difference
        $sizeDifference = $newFileSize - $oldFileSize;

        // Only check limit if file is getting larger
        if ($sizeDifference > 0) {
            // Find permission
            $permission = $this->permissionRepository->findByName('archive-library.archive-library*file.create');
            if (!$permission) {
                return;
            }

            // Get permission limit
            $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                $fileModel->company_id,
                $permission->id
            );

            if (!$permissionLimit) {
                return;
            }

            // Check if sufficient storage available
            if ($permissionLimit->actual_limit < $sizeDifference) {
                throw new UnauthorizedException(
                    403,
                    "Insufficient storage. Need {$sizeDifference} MB more (new: {$newFileSize} MB, old: {$oldFileSize} MB)."
                );
            }

            // Decrease limit
            $permissionLimit->decreaseLimit($sizeDifference);
        } elseif ($sizeDifference < 0) {
            // File is smaller - increase limit
            $permission = $this->permissionRepository->findByName('archive-library.archive-library*file.create');
            if ($permission) {
                $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                    $fileModel->company_id,
                    $permission->id
                );

                if ($permissionLimit) {
                    $permissionLimit->increaseLimit(abs($sizeDifference));
                }
            }
        }
    }
}
