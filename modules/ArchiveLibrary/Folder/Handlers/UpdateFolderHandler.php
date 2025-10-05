<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Handlers;

use Modules\ArchiveLibrary\Folder\Commands\UpdateFolderCommand;
use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;

class UpdateFolderHandler
{
    public function __construct(
        private FolderRepository $repository,
    ) {
    }

    public function handle(UpdateFolderCommand $updateFolderCommand)
    {
        $this->repository->updateFolder(
            $updateFolderCommand->getId(),
            $updateFolderCommand->toArray(),
            $updateFolderCommand->getUserIds()
        );
    }
}
