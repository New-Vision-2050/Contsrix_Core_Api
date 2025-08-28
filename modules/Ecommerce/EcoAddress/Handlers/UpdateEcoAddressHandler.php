<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Handlers;

use Modules\Ecommerce\EcoAddress\Commands\UpdateEcoAddressCommand;
use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;

class UpdateEcoAddressHandler
{
    public function __construct(
        private EcoAddressRepository $repository,
    ) {
    }

    public function handle(UpdateEcoAddressCommand $updateEcoAddressCommand)
    {
        $this->repository->updateEcoAddress($updateEcoAddressCommand->getId(), $updateEcoAddressCommand->toArray());
    }
}
