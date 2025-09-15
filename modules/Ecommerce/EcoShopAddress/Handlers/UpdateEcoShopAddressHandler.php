<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Handlers;

use Modules\Ecommerce\EcoShopAddress\Commands\UpdateEcoShopAddressCommand;
use Modules\Ecommerce\EcoShopAddress\Repositories\EcoShopAddressRepository;

class UpdateEcoShopAddressHandler
{
    public function __construct(
        private EcoShopAddressRepository $repository,
    ) {
    }

    public function handle(UpdateEcoShopAddressCommand $updateEcoShopAddressCommand)
    {
        $this->repository->updateEcoShopAddress($updateEcoShopAddressCommand->getId(), $updateEcoShopAddressCommand->toArray());
    }
}
