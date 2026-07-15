<?php

declare(strict_types=1);

namespace Modules\Country\Handlers;

use Modules\Country\Repositories\StateRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteStateHandler
{
    public function __construct(
        private StateRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteState($id);
    }
}
