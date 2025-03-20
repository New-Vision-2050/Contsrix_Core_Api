<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\File\DTO\CreateFileDTO;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Ramsey\Uuid\UuidInterface;

class FileCRUDService
{
    public function __construct(
        private FileRepository $repository,
    ) {
    }

    public function create(CreateFileDTO $createFileDTO): File
    {
         return $this->repository->createFile($createFileDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): File
    {
        return $this->repository->getFile(
            id: $id,
        );
    }
}
