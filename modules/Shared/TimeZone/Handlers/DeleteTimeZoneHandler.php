<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Handlers;

use Modules\Shared\TimeZone\Repositories\TimeZoneRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTimeZoneHandler
{
    public function __construct(
        private TimeZoneRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTimeZone($id);
    }
}
