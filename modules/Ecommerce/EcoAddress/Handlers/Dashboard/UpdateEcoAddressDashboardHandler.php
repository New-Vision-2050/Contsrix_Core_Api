<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Handlers\Dashboard;

use Modules\Ecommerce\EcoAddress\Commands\Dashboard\UpdateEcoAddressDashboardCommand;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;
use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEcoAddressDashboardHandler
{
    public function __construct(
        private EcoAddressRepository $repository,
    ) {
    }

    public function handle(UpdateEcoAddressDashboardCommand $updateEcoAddressCommand)
    {
        $this->repository->updateEcoAddress($updateEcoAddressCommand->getId(), $updateEcoAddressCommand->toArray());
    }
}
