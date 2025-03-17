<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Services;

use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\Folder\DTO\CreateFolderDTO;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;
use Ramsey\Uuid\UuidInterface;

class FolderCRUDService
{
    public function __construct(
        private FolderRepository $repository,
    ) {
    }

    public function create(CreateFolderDTO $createFolderDTO): Folder
    {
         return $this->repository->createFolder($createFolderDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        $folders = $this->repository->getFolderList(
            page: $page,
            perPage: $perPage,
        );

        return [
            'data' => $folders,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
            ],
        ];
    }
    public function listByParent(UuidInterface $parentId, int $page = 1, int $perPage = 10): array
    {
        $folders = $this->repository->getChildFolders($parentId, $page, $perPage);

        return [
            'data' => $folders,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $folders->count(),
            ],
        ];
    }

    public function get(UuidInterface $id): Folder
    {
        return $this->repository->getFolder(
            id: $id,
        );
    }
}
