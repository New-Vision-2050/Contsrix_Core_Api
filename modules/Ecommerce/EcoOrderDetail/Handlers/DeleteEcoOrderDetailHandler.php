<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Handlers;

use Modules\Ecommerce\EcoOrderDetail\Repositories\EcoOrderDetailRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoOrderDetailHandler
{
    public function __construct(
        private EcoOrderDetailRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoOrderDetail($id);
    }
}
