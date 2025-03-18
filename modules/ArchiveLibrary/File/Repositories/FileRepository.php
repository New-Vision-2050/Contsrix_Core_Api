<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\ArchiveLibrary\File\Models\File;

/**
 * @property File $model
 * @method File findOneOrFail($id)
 * @method File findOneByOrFail(array $data)
 */
class FileRepository extends BaseRepository
{
    public function __construct(File $model)
    {
        parent::__construct($model);
    }

    public function getFileList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFile(UuidInterface $id): File
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFile(array $data): File
    {
        return $this->create($data);
    }

    public function updateFile(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteFile(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
