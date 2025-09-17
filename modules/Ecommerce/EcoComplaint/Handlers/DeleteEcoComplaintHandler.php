<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Handlers;

use Modules\Ecommerce\EcoComplaint\Repositories\EcoComplaintRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoComplaintHandler
{
    public function __construct(
        private EcoComplaintRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoComplaint($id);
    }
}
