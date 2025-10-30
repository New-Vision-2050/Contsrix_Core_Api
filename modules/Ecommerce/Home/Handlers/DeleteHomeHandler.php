<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Handlers;

use Modules\Ecommerce\Home\Repositories\HomeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteHomeHandler
{
    public function __construct(
        private HomeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteHome($id);
    }
}
