<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Handlers;

use Modules\Ecommerce\DealDay\Repositories\DealDayRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteDealDayHandler
{
    public function __construct(
        private DealDayRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteDealDay($id);
    }
}
