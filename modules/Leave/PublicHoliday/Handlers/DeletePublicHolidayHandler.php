<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Handlers;

use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePublicHolidayHandler
{
    public function __construct(
        private PublicHolidayRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePublicHoliday($id);
    }
}
