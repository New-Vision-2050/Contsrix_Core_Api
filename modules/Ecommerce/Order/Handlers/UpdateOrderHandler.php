<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Handlers;

use Modules\Ecommerce\Order\Commands\UpdateOrderCommand;
use Modules\Ecommerce\Order\Repositories\OrderRepository;

class UpdateOrderHandler
{
    public function __construct(
        private OrderRepository $repository,
    ) {
    }

    public function handle(UpdateOrderCommand $updateOrderCommand)
    {
        $this->repository->updateOrder($updateOrderCommand->getId(), $updateOrderCommand->toArray());
    }
}
