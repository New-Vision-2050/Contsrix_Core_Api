<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Commands\UpdateSubEntityCommand;
use Modules\SubEntity\Repositories\SubEntityRepository;

class UpdateSubEntityHandler
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSubEntityCommand $updateSubEntityCommand)
    {
        $this->repository->updateSubEntity($updateSubEntityCommand->getId(), $updateSubEntityCommand->toArray());
    }
}
