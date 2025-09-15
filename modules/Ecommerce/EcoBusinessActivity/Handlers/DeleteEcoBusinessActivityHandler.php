<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Handlers;

use Modules\Ecommerce\EcoBusinessActivity\Repositories\EcoBusinessActivityRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoBusinessActivityHandler
{
    public function __construct(
        private EcoBusinessActivityRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoBusinessActivity($id);
    }
}
