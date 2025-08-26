<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Handlers;

use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoProductHandler
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoProduct($id);
    }
}
