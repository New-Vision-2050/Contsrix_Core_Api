<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Handlers;

use Modules\Ecommerce\EcoOrderDetail\Commands\UpdateEcoOrderDetailCommand;
use Modules\Ecommerce\EcoOrderDetail\Repositories\EcoOrderDetailRepository;

class UpdateEcoOrderDetailHandler
{
    public function __construct(
        private EcoOrderDetailRepository $repository,
    ) {
    }

    public function handle(UpdateEcoOrderDetailCommand $updateEcoOrderDetailCommand)
    {
        $this->repository->updateEcoOrderDetail($updateEcoOrderDetailCommand->getId(), $updateEcoOrderDetailCommand->toArray());
    }
}
