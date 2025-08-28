<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Handlers;

use Modules\Ecommerce\EcoOrder\Repositories\EcoOrderRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoOrderHandler
{
    public function __construct(
        private EcoOrderRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoOrder($id);
    }
}
