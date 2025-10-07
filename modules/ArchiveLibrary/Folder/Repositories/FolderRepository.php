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
        $query = $this->model->query();

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
        return $this->paginatedList(['parent_id' => $parentId->toString()], $page, $perPage);
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
            if ($file == null) {
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

    public function getFoldersAndFilesByParent(?string $parentId, $userId): array
    {
        // Query folders based on parent_id
        $foldersQuery = $this->model->query();

        if ($parentId === null) {
            $foldersQuery->whereNull('parent_id');
        } else {
            $folder = $this->model->query()->where('id', $parentId)->first();
            if ($folder->password != null  &&( !request()->has("password") || !Hash::check(request()->get("password"), $folder->password))) {
                throw new CustomException(__("validation.access-denied"));
            }
            $foldersQuery->where('parent_id', $parentId);
        }

        // Get all folders
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

        // Query files based on parent_id (folder_id)
        $filesQuery = File::query();

        if ($parentId === null) {
            $filesQuery->whereNull('folder_id');
        } else {
            $filesQuery->where('folder_id', $parentId);
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

        return [
            'folders' => $folders,
            'files' => $files,
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
