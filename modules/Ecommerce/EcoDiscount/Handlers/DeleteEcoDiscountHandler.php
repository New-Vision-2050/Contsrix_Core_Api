<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Handlers;

use Modules\Ecommerce\EcoDiscount\Repositories\EcoDiscountRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoDiscountHandler
{
    public function __construct(
        private EcoDiscountRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoDiscount($id);
    }
}
