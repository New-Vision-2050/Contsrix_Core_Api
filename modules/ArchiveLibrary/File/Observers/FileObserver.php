<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Observers;

use Illuminate\Support\Facades\Log;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;
use Spatie\Permission\Exceptions\UnauthorizedException;

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
        } catch (\Exception $e) {
            Log::error('Failed to process file storage limit after creation', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the File "updating" event.
     * Check and adjust storage limit BEFORE file is updated
     */
    public function updating(File $file): void
    {
        try {
            // Only process if media changed
            if (!$this->wasMediaChanged($file)) {
                return;
            }

            $newFileSize = $this->getFileSizeInMB($file);
            $oldFileSize = $this->getOldFileSizeInMB($file);

            if ($newFileSize <= 0 && $oldFileSize <= 0) {
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

            // Calculate size difference
            $sizeDifference = $newFileSize - $oldFileSize;

            if ($sizeDifference > 0) {
                // New file is larger - consume more storage
                if ($permissionLimit->actual_limit < $sizeDifference) {
                    throw new UnauthorizedException(
                        403,
                        "Insufficient storage. Need {$sizeDifference} MB more (new: {$newFileSize} MB, old: {$oldFileSize} MB)."
                    );
                }
                $permissionLimit->decreaseLimit($sizeDifference);

                Log::info('File storage limit decreased on update', [
                    'file_id' => $file->id,
                    'size_difference_mb' => $sizeDifference,
                    'remaining_limit_mb' => $permissionLimit->actual_limit
                ]);
            } elseif ($sizeDifference < 0) {
                // New file is smaller - free up storage
                $permissionLimit->increaseLimit(abs($sizeDifference));

                Log::info('File storage limit increased on update', [
                    'file_id' => $file->id,
                    'size_difference_mb' => abs($sizeDifference),
                    'remaining_limit_mb' => $permissionLimit->actual_limit
                ]);
            }

        } catch (UnauthorizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to process file storage limit on update', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
        }
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
        $media = $file->getFirstMedia();
        if ($media && $media->size) {
            return round($media->size / (1024 * 1024),2);
        }

        // Fallback to mediaFile relation (integrated files)
        if ($file->mediaFile && $file->mediaFile->size) {
            return  round($file->mediaFile->size / (1024 * 1024),2);
        }

        return 0;
    }

    /**
     * Get old file size from original model state
     */
    private function getOldFileSizeInMB(File $file): int
    {
        // Get original file from database
        $originalFile = File::find($file->id);
        if (!$originalFile) {
            return 0;
        }

        return $this->getFileSizeInMB($originalFile);
    }

    /**
     * Check if media was changed during update
     */
    private function wasMediaChanged(File $file): bool
    {
        // Check if media relationship was loaded and modified
        if ($file->relationLoaded('media')) {
            return true;
        }

        // Check if mediaFile relationship was loaded and modified
        if ($file->relationLoaded('mediaFile')) {
            return true;
        }

        return false;
    }
}
