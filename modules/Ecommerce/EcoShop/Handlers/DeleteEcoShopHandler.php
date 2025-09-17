<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Handlers;

use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoShopHandler
{
    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoShop($id);
    }
}
