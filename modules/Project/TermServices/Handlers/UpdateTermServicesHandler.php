<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Handlers;

use Modules\Project\TermServices\Commands\UpdateTermServicesCommand;
use Modules\Project\TermServices\Repositories\TermServicesRepository;

class UpdateTermServicesHandler
{
    public function __construct(
        private TermServicesRepository $repository,
    ) {
    }

    public function handle(UpdateTermServicesCommand $updateTermServicesCommand)
    {
        $this->repository->updateTermServices($updateTermServicesCommand->getId(), $updateTermServicesCommand->toArray());
    }
}
