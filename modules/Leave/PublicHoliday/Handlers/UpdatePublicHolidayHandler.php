<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Handlers;

use Modules\Leave\PublicHoliday\Commands\UpdatePublicHolidayCommand;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;

class UpdatePublicHolidayHandler
{
    public function __construct(
        private PublicHolidayRepository $repository,
    ) {
    }

    public function handle(UpdatePublicHolidayCommand $updatePublicHolidayCommand)
    {
        $this->repository->updatePublicHoliday($updatePublicHolidayCommand->getId(), $updatePublicHolidayCommand->toArray());
    }
}
