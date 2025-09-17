<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Handlers;

use Modules\Ecommerce\EcoOrder\Commands\UpdateEcoOrderCommand;
use Modules\Ecommerce\EcoOrder\Repositories\EcoOrderRepository;

class UpdateEcoOrderHandler
{
    public function __construct(
        private EcoOrderRepository $repository,
    ) {
    }

    public function handle(UpdateEcoOrderCommand $updateEcoOrderCommand)
    {
        $this->repository->updateEcoOrder($updateEcoOrderCommand->getId(), $updateEcoOrderCommand->toArray());
    }
}
