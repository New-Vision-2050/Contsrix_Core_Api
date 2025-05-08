<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Handlers;

use Modules\Shared\TypeWorkingHour\Repositories\TypeWorkingHourRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTypeWorkingHourHandler
{
    public function __construct(
        private TypeWorkingHourRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTypeWorkingHour($id);
    }
}
