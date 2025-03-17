<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Handlers;

use Modules\Shared\TimeZone\Commands\UpdateTimeZoneCommand;
use Modules\Shared\TimeZone\Repositories\TimeZoneRepository;

class UpdateTimeZoneHandler
{
    public function __construct(
        private TimeZoneRepository $repository,
    ) {
    }

    public function handle(UpdateTimeZoneCommand $updateTimeZoneCommand)
    {
        $this->repository->updateTimeZone($updateTimeZoneCommand->getId(), $updateTimeZoneCommand->toArray());
    }
}
