<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Handlers;

use Modules\Ecommerce\Home\Commands\UpdateHomeCommand;
use Modules\Ecommerce\Home\Repositories\HomeRepository;

class UpdateHomeHandler
{
    public function __construct(
        private HomeRepository $repository,
    ) {
    }

    public function handle(UpdateHomeCommand $updateHomeCommand)
    {
        $this->repository->updateHome($updateHomeCommand->getId(), $updateHomeCommand->toArray());
    }
}
