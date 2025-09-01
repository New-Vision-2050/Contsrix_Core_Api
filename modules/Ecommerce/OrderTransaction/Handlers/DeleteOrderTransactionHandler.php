<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Handlers;

use Modules\Ecommerce\OrderTransaction\Repositories\OrderTransactionRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteOrderTransactionHandler
{
    public function __construct(
        private OrderTransactionRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteOrderTransaction($id);
    }
}
