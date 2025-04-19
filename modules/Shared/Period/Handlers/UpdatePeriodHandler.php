<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Handlers;

use Modules\Shared\Period\Commands\UpdatePeriodCommand;
use Modules\Shared\Period\Repositories\PeriodRepository;

class UpdatePeriodHandler
{
    public function __construct(
        private PeriodRepository $repository,
    ) {
    }

    public function handle(UpdatePeriodCommand $updatePeriodCommand)
    {
        $this->repository->updatePeriod($updatePeriodCommand->getId(), $updatePeriodCommand->toArray());
    }
}
