<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Handlers\Customer;

use Modules\Ecommerce\EcoAddress\Commands\Customer\DeleteEcoAddressCustomerCommand;
use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoAddressCustomerHandler
{
    public function __construct(
        private EcoAddressRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoAddress($id);
    }
}
