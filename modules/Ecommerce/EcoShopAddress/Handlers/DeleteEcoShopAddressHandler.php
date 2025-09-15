<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Handlers;

use Modules\Ecommerce\EcoShopAddress\Repositories\EcoShopAddressRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoShopAddressHandler
{
    public function __construct(
        private EcoShopAddressRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoShopAddress($id);
    }
}
