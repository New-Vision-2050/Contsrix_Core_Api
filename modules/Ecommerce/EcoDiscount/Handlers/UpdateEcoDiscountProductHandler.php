<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Handlers;

use Modules\Ecommerce\EcoDiscount\Commands\UpdateEcoDiscountProductCommand;
use Modules\Ecommerce\EcoDiscount\Repositories\EcoDiscountRepository;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;

class UpdateEcoDiscountProductHandler
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function handle(UpdateEcoDiscountProductCommand $updateEcoDiscountProductCommand)
    {
        $this->repository->updateDiscountProduct($updateEcoDiscountProductCommand->getId(), $updateEcoDiscountProductCommand->toArray());
    }
}
