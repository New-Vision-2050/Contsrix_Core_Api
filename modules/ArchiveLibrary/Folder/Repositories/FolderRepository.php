<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Mockery\Exception;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Models\UserFilePermission;
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
    public function __construct(Folder $model)
    {
        parent::__construct($model);
    }

    public function getFolderList(int $page, int $perPage = 10, ?UuidInterface $parentId = null)
    {
        $query = $this->model->query();

        if ($parentId !=null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getFolder( $id): Folder
    {

        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }
    public function getChildFolders(UuidInterface $parentId, int $page = 1, int $perPage = 10)
    {
        return $this->paginatedList(['parent_id' => $parentId->toString()], $page, $perPage);
    }
    public function createFolder(array $data , array $userIds): Folder
    {
        try{
            $folder  = $this->create($data);
            $folder->users()->attach($userIds);

        }catch (Exception $e){
            throw new CustomException(__("validation.create-not-successful"));
        }
        return $folder;
    }



    public function updateFolder(UuidInterface $id, array $data, array $userIds = []): bool
    {
        try {
            $folder = $this->getFolder($id);

            // Update folder attributes
            $updated = $this->update($id, $data);

            // Sync user relationships - this will remove old users and add new ones
            if (!empty($userIds) || $folder->access_type === 'private') {
                $folder->users()->sync($userIds);
            }

            return $updated;
        } catch (\Exception $e) {
            throw new CustomException(__("validation.update-not-successful"));
        }
    }

    public function deleteFolder(UuidInterface $id): bool
    {
        $folder = $this->getFolder($id);
        if(count($folder->children) !=0)
            throw new CustomException(__("validation.can-not-delete-has-children"));
        if(count($folder->files) !=0)
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
    public function getViewableFilesInFolder( $folderId,  $userId): Collection
    {
        $files = File::where('folder_id', $folderId)->get();

        return $files->filter(function (File $file) use ($userId) {
            return $this->canViewFile($file->id,$userId);
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
}
