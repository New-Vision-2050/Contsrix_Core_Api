<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Handlers;

use Modules\Shared\Period\Repositories\PeriodRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePeriodHandler
{
    public function __construct(
        private PeriodRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePeriod($id);
    }
}
