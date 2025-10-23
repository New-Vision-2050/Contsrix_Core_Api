<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Handlers;

use Modules\Ecommerce\DealDay\Commands\UpdateDealDayCommand;
use Modules\Ecommerce\DealDay\Repositories\DealDayRepository;

class UpdateDealDayHandler
{
    public function __construct(
        private DealDayRepository $repository,
    ) {
    }

    public function handle(UpdateDealDayCommand $updateDealDayCommand)
    {
        $this->repository->updateDealDay($updateDealDayCommand->getId(), $updateDealDayCommand->toArray());
    }
}
