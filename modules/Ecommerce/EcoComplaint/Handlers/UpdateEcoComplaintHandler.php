<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Handlers;

use Modules\Ecommerce\EcoComplaint\Commands\UpdateEcoComplaintCommand;
use Modules\Ecommerce\EcoComplaint\Repositories\EcoComplaintRepository;

class UpdateEcoComplaintHandler
{
    public function __construct(
        private EcoComplaintRepository $repository,
    ) {
    }

    public function handle(UpdateEcoComplaintCommand $updateEcoComplaintCommand)
    {
        $this->repository->updateEcoComplaint($updateEcoComplaintCommand->getId(), $updateEcoComplaintCommand->toArray());
    }
}
