<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Mockery\Exception;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Models\UserFilePermission;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Models\UserFolderPermission;

/**
 * @property Folder $model
 * @method Folder findOneOrFail($id)
 * @method Folder findOneByOrFail(array $data)
 */
class FolderRepository extends BaseRepository
{
    public function __construct(Folder $model, private FileUploadService $uploadedFile)
    {
        parent::__construct($model);
    }

    public function getFolderList(int $page, int $perPage = 10, ?UuidInterface $parentId = null)
    {
        $query = $this->model->query()->withCount('files');

        if ($parentId != null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getFolder($id): Folder
    {

        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }

    public function getChildFolders(UuidInterface $parentId, int $page = 1, int $perPage = 10)
    {
        return $this->model->query()
            ->withCount('files')
            ->where('parent_id', $parentId->toString())
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function createFolder(array $data, array $userIds, ?UploadedFile $file = null): Folder
    {
        try {
            $folder = $this->create($data);
            $folder->users()->attach($userIds);
            if ($file)
                $this->uploadedFile->uploadFile($folder, $file, 'upload');

        } catch (Exception $e) {
            throw new CustomException(__("validation.create-not-successful"));
        }
        return $folder;
    }


    public function updateFolder(UuidInterface $id, array $data, array $userIds = [], ?UploadedFile $file = null): bool
    {
        try {
            $folder = $this->getFolder($id);

            // Update folder attributes
            $updated = $this->update($id, $data);

            // Sync user relationships - this will remove old users and add new ones
            if (!empty($userIds) || $folder->access_type === 'private') {
                $folder->users()->sync($userIds);
            }
            if ($file != null) {
                $folder->clearMediaCollection('upload');
                $this->uploadedFile->uploadFile($folder, $file, 'upload');
            }

            return $updated;
        } catch (\Exception $e) {
            throw new CustomException(__("validation.update-not-successful"));
        }
    }

    public function deleteFolder(UuidInterface $id): bool
    {
        $folder = $this->getFolder($id);
        if (count($folder->children) != 0)
            throw new CustomException(__("validation.can-not-delete-has-children"));
        if (count($folder->files) != 0)
            throw new CustomException(__("validation.can-not-delete-has-children"));

        return $this->delete($id);
    }

    public function canViewFolder($folderId, $userId): bool
    {
        $folder = $this->getFolder($folderId);

        // if ($folder->access_type === 'public') {
        return true;
        // }

        // return UserFolderPermission::where('folder_id', $folderId)
        //     ->where('user_id', $userId)
        //     ->where('permission_type', 'view')
        //     ->exists();
    }

    public function getViewableFilesInFolder($folderId, $userId): Collection
    {
        $files = File::where('folder_id', $folderId)->get();

        return $files->filter(function (File $file) use ($userId) {
            return $this->canViewFile($file->id, $userId);
        });
    }

    public function canViewFile(UuidInterface $fileId, UuidInterface $userId): bool
    {
        $file = File::find($fileId);

        // If the file is public, it's accessible to everyone
        if ($file->access_type === 'public') {
            return true;
        }

        // Check if the user has the 'view' permission for this file
        return UserFilePermission::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->where('permission_type', 'view')
            ->exists();
    }

    public function getFoldersAndFilesByParent(
        ?string $parentId,
        $userId,
        int $page = 1,
        int $perPage = 10,
        ?string $documentType = null,
        ?bool $isFavourite = null,
        ?string $endDate = null,
        ?string $endDateFrom = null,
        ?string $endDateTo = null,
        ?string $search = null,
        string $searchType = 'all',
        ?int $branchId = null,
        ?string $sort = null
    )
    {
        // Check password first if parent folder is provided
        if ($parentId !== null) {
            $folder = $this->model->query()->where('id', $parentId)->first();
            if ($folder && $folder->password != null && (!request()->has("password") || !Hash::check(request()->get("password"), $folder->password))) {
                throw new CustomException(__("validation.access-denied"));
            }
        }

        // Check if any file-specific filter is provided
        $hasFileFilters = $documentType !== null
            || $isFavourite !== null
            || $endDate !== null
            || $endDateFrom !== null
            || $endDateTo !== null
            || ($search !== null && $search !== ''||$branchId!==null);

        // If file filters are provided, return empty folders array
        if ($hasFileFilters) {
            $folders = collect();
        } else {
            // Query folders based on parent_id
            $foldersQuery = $this->model->query()->withCount('files');

            if ($parentId != null) {
                $foldersQuery->where('parent_id', $parentId);
            }
            else{
                $foldersQuery->whereNull('parent_id');
            }

            // Get all folders with files count
            $allFolders = $foldersQuery->get();

            // Filter folders based on access type and permissions
            $folders = $allFolders->filter(function ($folder) use ($userId) {
                if ($folder->access_type === 'public') {
                    return true;
                }

                // Check if user has permission for private folder
                return UserFolderPermission::where('folder_id', $folder->id)
                    ->where('user_id', $userId)
                    ->exists();
            })->values();

            // Apply sorting to folders if sort parameter is provided
            if ($sort !== null) {
                if ($sort === 'asc') {
                    $folders = $folders->sortBy('name')->values();
                } elseif ($sort === 'desc') {
                    $folders = $folders->sortByDesc('name')->values();
                }
            }
        }

        // Query files based on parent_id (folder_id)
        $filesQuery = File::query();

        if ($parentId != null) {
            $filesQuery->where('folder_id', $parentId);
        }elseif ($parentId==null &&!$hasFileFilters )
        {
            $filesQuery->whereNull('folder_id');

        }

        // Filter by document type (MIME type) if provided
        if ($documentType !== null && $documentType !== 'fav') {
            $mimeTypeMap = [
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'txt' => 'text/plain',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'csv' => 'text/csv',
            ];

            $mimeType = $mimeTypeMap[$documentType] ?? null;

            if ($mimeType) {
                $filesQuery->whereHas('media', function ($query) use ($mimeType) {
                    $query->where('mime_type', $mimeType);
                });
            }
        }

        // Filter by favourite status if provided
        if ($isFavourite !== null && $isFavourite === true) {
            $filesQuery->whereHas('favouritedByUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        }

        // Filter by end_date if provided
        if ($endDate !== null) {
            // Exact date match
            $filesQuery->whereDate('end_date', $endDate);
        } elseif ($endDateFrom !== null || $endDateTo !== null) {
            // Date range filter
            if ($endDateFrom !== null) {
                $filesQuery->whereDate('end_date', '>=', $endDateFrom);
            }
            if ($endDateTo !== null) {
                $filesQuery->whereDate('end_date', '<=', $endDateTo);
            }
        }

        // Apply search filter based on search type
        if ($search !== null && $search !== '') {
            $filesQuery->where(function ($query) use ($search, $searchType) {
                if ($searchType === 'name') {
                    // Search only in name
                    $query->where('name', 'LIKE', '%' . $search . '%');
                } elseif ($searchType === 'reference_number') {
                    // Search only in reference_number
                    $query->where('reference_number', 'LIKE', '%' . $search . '%');
                } elseif ($searchType=="employee")
                {
                    $query->where('type', "employee");


                }
                else {
                    // Search in both name and reference_number (type = 'all')
                    $query->where('name', 'LIKE', '%' . $search . '%')
                          ->orWhere('reference_number', 'LIKE', '%' . $search . '%');
                }
            });
        }

        // Filter by branch_id (management_hierarchy_id) if provided
        if ($branchId !== null) {
            $filesQuery->where('management_hierarchy_id', $branchId);
        }


        // Get all files
        $allFiles = $filesQuery->get();

        // Filter files based on access type and permissions
        $files = $allFiles->filter(function ($file) use ($userId) {
            if ($file->access_type === 'public') {
                return true;
            }

            // Check if user has permission for private file
            return UserFilePermission::where('file_id', $file->id)
                ->where('user_id', $userId)
                ->exists();
        })->values();

        // Apply sorting to files if sort parameter is provided
        if ($sort !== null) {
            if ($sort === 'asc') {
                $files = $files->sortBy('name')->values();
            } elseif ($sort === 'desc') {
                $files = $files->sortByDesc('name')->values();
            }
        }
        // Calculate total items and pagination
        $totalFolders = $folders->count();
        $totalFiles = $files->count();
        $totalItems = $totalFolders + $totalFiles;
        $totalPages = (int)ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;

        // Determine how many folders and files to include in this page
        $paginatedFolders = collect();
        $paginatedFiles = collect();

        if ($offset < $totalFolders) {
            // We're still showing folders
            $foldersToTake = min($perPage, $totalFolders - $offset);
            $paginatedFolders = $folders->slice($offset, $foldersToTake);

            // If we have room for files on this page
            $remainingSlots = $perPage - $foldersToTake;
            if ($remainingSlots > 0) {
                $paginatedFiles = $files->slice(0, $remainingSlots);
            }
        } else {
            // We're past all folders, showing only files
            $fileOffset = $offset - $totalFolders;
            $paginatedFiles = $files->slice($fileOffset, $perPage);
        }

        return [
            'folders' => $paginatedFolders->values(),
            'files' => $paginatedFiles->values(),
            'pagination' => [
                'total' => $totalItems,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => $totalItems > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalItems),
            ],
        ];
    }


    public function getUsersAllowedByFolderId($folderId)
    {
        $userIds = UserFolderPermission::where('folder_id', $folderId)->pluck("user_id")->toArray();
        if (count($userIds)) {
            return User::query()->whereIn("id", $userIds)->get();
        } else {
            return User::query()->where("company_id", tenant("id"))->get();
        }
    }
}
