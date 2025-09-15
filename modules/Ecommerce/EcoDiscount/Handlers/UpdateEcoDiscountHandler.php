<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Handlers;

use Modules\Ecommerce\EcoDiscount\Commands\UpdateEcoDiscountCommand;
use Modules\Ecommerce\EcoDiscount\Repositories\EcoDiscountRepository;

class UpdateEcoDiscountHandler
{
    public function __construct(
        private EcoDiscountRepository $repository,
    ) {
    }

    public function handle(UpdateEcoDiscountCommand $updateEcoDiscountCommand)
    {
        $this->repository->updateEcoDiscount($updateEcoDiscountCommand->getId(), $updateEcoDiscountCommand->toArray());
    }
}
