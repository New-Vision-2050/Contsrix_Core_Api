<?php

declare(strict_types=1);

namespace Modules\Shared\University\Handlers;

use Modules\Shared\University\Commands\UpdateUniversityCommand;
use Modules\Shared\University\Repositories\UniversityRepository;

class UpdateUniversityHandler
{
    public function __construct(
        private UniversityRepository $repository,
    ) {
    }

    public function handle(UpdateUniversityCommand $updateUniversityCommand)
    {
        $this->repository->updateUniversity($updateUniversityCommand->getId(), $updateUniversityCommand->toArray());
    }
}
