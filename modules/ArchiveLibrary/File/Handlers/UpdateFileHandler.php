<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Handlers;

use Modules\ArchiveLibrary\File\Commands\UpdateFileCommand;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;

class UpdateFileHandler
{
    public function __construct(
        private FileRepository $repository,
    ) {
    }

    public function handle(UpdateFileCommand $updateFileCommand)
    {
        $this->repository->updateFile($updateFileCommand->getId(), $updateFileCommand->toArray());
    }
}
