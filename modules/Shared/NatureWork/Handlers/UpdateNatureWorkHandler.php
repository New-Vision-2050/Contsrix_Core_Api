<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Handlers;

use Modules\Shared\NatureWork\Commands\UpdateNatureWorkCommand;
use Modules\Shared\NatureWork\Repositories\NatureWorkRepository;

class UpdateNatureWorkHandler
{
    public function __construct(
        private NatureWorkRepository $repository,
    ) {
    }

    public function handle(UpdateNatureWorkCommand $updateNatureWorkCommand)
    {
        $this->repository->updateNatureWork($updateNatureWorkCommand->getId(), $updateNatureWorkCommand->toArray());
    }
}
