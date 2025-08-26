<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Handlers;

use Modules\Ecommerce\Warehous\Repositories\WarehousRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWarehousHandler
{
    public function __construct(
        private WarehousRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWarehous($id);
    }
}
