<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Handlers;

use Modules\Ecommerce\EcoShop\Commands\UpdateEcoShopCommand;
use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;

class UpdateEcoShopHandler
{
    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    public function handle(UpdateEcoShopCommand $updateEcoShopCommand)
    {
        $this->repository->updateEcoShop($updateEcoShopCommand->getId(), $updateEcoShopCommand->toArray());
    }
}
