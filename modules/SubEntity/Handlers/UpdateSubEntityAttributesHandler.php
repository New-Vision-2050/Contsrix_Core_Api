<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Commands\UpdateSubEntityAttributesCommand;
use Modules\SubEntity\Repositories\SubEntityRepository;

class UpdateSubEntityAttributesHandler
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSubEntityAttributesCommand $updateSubEntityAttributesCommand)
    {
        $this->repository->updateSubEntityAttributes($updateSubEntityAttributesCommand->getId(), $updateSubEntityAttributesCommand->toArray());
    }
}
