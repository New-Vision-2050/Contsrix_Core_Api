<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Handlers;

use Modules\Ecommerce\EcoProduct\Commands\UpdateEcoProductCommand;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;

class UpdateEcoProductHandler
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function handle(UpdateEcoProductCommand $updateEcoProductCommand)
    {
        $this->repository->updateEcoProduct($updateEcoProductCommand->getId(), $updateEcoProductCommand->toArray());
    }
}
