<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Handlers\Customer;

use Modules\Ecommerce\EcoAddress\Commands\Customer\UpdateEcoAddressCustomerCommand;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;
use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateEcoAddressCustomerHandler
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
