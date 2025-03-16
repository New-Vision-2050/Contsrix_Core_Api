<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Handlers;

use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFolderHandler
{
    public function __construct(
        private FolderRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFolder($id);
    }
}
