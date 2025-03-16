<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\ArchiveLibrary\Folder\Models\Folder;

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

    public function getFolderList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['parent_id'=>null], $page, $perPage);
    }

    public function getFolder(UuidInterface $id): Folder
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }
    public function getChildFolders(UuidInterface $parentId, ?int $page = 1, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['parent_id' => $parentId->toString()], $page, $perPage);
    }
    public function createFolder(array $data): Folder
    {
        return $this->create($data);
    }

    public function updateFolder(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteFolder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
