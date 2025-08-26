<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Handlers;

use Modules\Ecommerce\EcoBrand\Commands\UpdateEcoBrandCommand;
use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;

class UpdateEcoBrandHandler
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBrandCommand $updateEcoBrandCommand)
    {
        $this->repository->updateEcoBrand($updateEcoBrandCommand->getId(), $updateEcoBrandCommand->toArray());
    }
}
