<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Handlers;

use Modules\Ecommerce\OrderTransaction\Commands\UpdateOrderTransactionCommand;
use Modules\Ecommerce\OrderTransaction\Repositories\OrderTransactionRepository;

class UpdateOrderTransactionHandler
{
    public function __construct(
        private OrderTransactionRepository $repository,
    ) {
    }

    public function handle(UpdateOrderTransactionCommand $updateOrderTransactionCommand)
    {
        $this->repository->updateOrderTransaction($updateOrderTransactionCommand->getId(), $updateOrderTransactionCommand->toArray());
    }
}
