<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Handlers;

use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoAddressHandler
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
