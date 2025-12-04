<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Observers;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;
use Spatie\Permission\Exceptions\UnauthorizedException;
use function PHPUnit\Framework\throwException;

class FileObserver
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private CompanyPermissionLimitRepository $companyPermissionLimitRepository
    ) {}

    /**
     * Handle the File "creating" event.
     * Check storage limit BEFORE file record is created
     * Note: At this point, media is not attached yet, so we can only do basic validation
     */
    public function creating(File $file): void
    {
        try {
            // Basic validation: check if company has any storage left
            if (!$file->company_id) {
                return;
            }

            // Find the archive library file create permission
            $permission = $this->permissionRepository->findByName('archive-library.archive-library*file.create');
            if (!$permission) {
                return;
            }

            // Get permission limit for this company
            $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                $file->company_id,
                $permission->id
            );

            if (!$permissionLimit) {
                return;
            }

            // Check if limit is completely exhausted (0 MB left)
            if ($permissionLimit->isLimitExceeded()) {
                throw new UnauthorizedException(
                    403,
                    "Storage limit exceeded. No more storage available."
                );
            }

        } catch (UnauthorizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to check storage limit before file creation', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the File "created" event.
     * Decrease storage limit AFTER file and media are saved
     */
    public function created(File $file): void
    {

        try {
            // Get file size from media or mediaFile (should be attached by now)
            $fileSize = $this->getFileSizeInMB($file);

            if ($fileSize <= 0) {
                // No media attached yet, will be handled when media is added
                return;
            }

            if (!$file->company_id) {
                Log::warning('File created without company_id, skipping storage limit check', [
                    'file_id' => $file->id
                ]);
                return;
            }

            // Find the archive library file create permission
            $permission = $this->permissionRepository->findByName('archive-library.archive-library*file.create');
            if (!$permission) {
                return;
            }

            // Get permission limit for this company
            $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                $file->company_id,
                $permission->id
            );

            if (!$permissionLimit) {
                return;
            }

            // Double-check if file size exceeds remaining limit
            if ($permissionLimit->actual_limit < $fileSize) {
                // Delete the file record since we can't store it
                $file->delete();

                throw new UnauthorizedException(
                    403,
                    "File size ({$fileSize} MB) exceeds remaining storage limit ({$permissionLimit->actual_limit} MB). File was not saved."
                );
            }

            // Decrease the storage limit
            $permissionLimit->decreaseLimit($fileSize);

            Log::info('File storage limit decreased after creation', [
                'file_id' => $file->id,
                'company_id' => $file->company_id,
                'file_size_mb' => $fileSize,
                'remaining_limit_mb' => $permissionLimit->actual_limit
            ]);

        } catch (UnauthorizedException $e) {
            throw $e;
        }


    }

    /**
     * Handle the File "updating" event.
     * Note: File update storage limits are handled in FileRepository
     * because media upload happens after model update in the workflow.
     * This observer only handles file creation and deletion.
     */
    public function updating(File $file): void
    {
        // File update limits are handled in FileRepository::checkStorageLimitForUpdate()
        // because the workflow is: update model -> upload media
        // Observer fires during update, but media isn't available yet
    }

    /**
     * Handle the File "deleting" event.
     * Restore storage limit when file is deleted
     */
    public function deleting(File $file): void
    {
        try {
            // Get file size before deletion
            $fileSize = $this->getFileSizeInMB($file);

            if ($fileSize <= 0) {
                return;
            }

            if (!$file->company_id) {
                return;
            }

            // Find permission
            $permission = $this->permissionRepository->findByName('archive-library.archive-library*file.create');
            if (!$permission) {
                return;
            }

            // Get permission limit
            $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                $file->company_id,
                $permission->id
            );

            if (!$permissionLimit) {
                return;
            }

            // Restore the storage limit
            $permissionLimit->increaseLimit($fileSize);

            Log::info('File storage limit restored on deletion', [
                'file_id' => $file->id,
                'company_id' => $file->company_id,
                'file_size_mb' => $fileSize,
                'remaining_limit_mb' => $permissionLimit->actual_limit
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to restore file storage limit on deletion', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            // Don't block deletion on limit tracking errors
        }
    }

    /**
     * Get file size in MB from media or mediaFile
     */
    private function getFileSizeInMB(File $file)
    {
        // Try to get from Spatie media first (direct uploads)
        $media = $file->getFirstMedia("upload");
        if ($media && $media->size) {
            return round($media->size / (1024 * 1024),2);
        }

        // Fallback to mediaFile relation (integrated files)
        if ($file->mediaFile && $file->mediaFile->size) {
            return  round($file->mediaFile->size / (1024 * 1024),2);
        }


        // Fallback to request for single file upload or array of files named 'file'
        if (function_exists('request') && request()->hasFile('file')) {
            $uploadedData = request()->file('file');
            if (is_array($uploadedData)) {
                $totalSize = 0;
                foreach ($uploadedData as $fileItem) {
                    if ($fileItem && $fileItem->isValid()) {
                        $totalSize += $fileItem->getSize();
                    }
                }
                 if ($totalSize > 0) {
                    return round($totalSize / (1024 * 1024), 2);
                }
            } elseif ($uploadedData && $uploadedData->isValid()) {
                $sizeInBytes = $uploadedData->getSize();
                return round($sizeInBytes / (1024 * 1024), 2);
            }
        }

        // Fallback to request for multiple files upload
        if (function_exists('request') && request()->hasFile('files')) {
            $uploadedFiles = request()->file('files');
            $totalSize = 0;

            if (is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile && $uploadedFile->isValid()) {
                        $totalSize += $uploadedFile->getSize();
                    }
                }
            }

            if ($totalSize > 0) {
                return round($totalSize / (1024 * 1024), 2);
            }
        }

        return 0;
    }

    /**
     * Get old file size from original model state
     */
    private function getNewFileSizeInMB(File $file)
    {
        // Check for single file upload or array of files named 'file'
        if (function_exists('request') && request()->hasFile('file')) {
            $uploadedData = request()->file('file');
            if (is_array($uploadedData)) {
                $totalSize = 0;
                foreach ($uploadedData as $fileItem) {
                    if ($fileItem && $fileItem->isValid()) {
                        $totalSize += $fileItem->getSize();
                    }
                }
                 if ($totalSize > 0) {
                    return round($totalSize / (1024 * 1024), 2);
                }
            } elseif ($uploadedData && $uploadedData->isValid()) {
                $sizeInBytes = $uploadedData->getSize();
                return round($sizeInBytes / (1024 * 1024), 2);
            }
        }

        // Check for multiple files upload
        if (function_exists('request') && request()->hasFile('files')) {
            $uploadedFiles = request()->file('files');
            $totalSize = 0;

            if (is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile && $uploadedFile->isValid()) {
                        $totalSize += $uploadedFile->getSize();
                    }
                }
            }

            if ($totalSize > 0) {
                return round($totalSize / (1024 * 1024), 2);
            }
        }

        return 0;
    }

    /**
     * Check if media was changed during update
     */
    private function wasMediaChanged(File $file): bool
    {
        // Check for single file upload or array of files named 'file'
        if (function_exists('request') && request()->hasFile('file')) {
            $uploadedData = request()->file('file');

            if (is_array($uploadedData)) {
                foreach ($uploadedData as $fileItem) {
                    if ($fileItem && $fileItem->isValid()) {
                        return true;
                    }
                }
            } elseif ($uploadedData && $uploadedData->isValid()) {
                return true;
            }
        }

        // Check for multiple files upload
        if (function_exists('request') && request()->hasFile('files')) {
            $uploadedFiles = request()->file('files');
            if (is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile && $uploadedFile->isValid()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
