<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Handlers;

use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFileHandler
{
    public function __construct(
        private FileRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFile($id);
    }
}
