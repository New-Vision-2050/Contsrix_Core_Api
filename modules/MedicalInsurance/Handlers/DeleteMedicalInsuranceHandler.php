<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Modules\MedicalInsurance\Repositories\MedicalInsuranceRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMedicalInsuranceHandler
{
    public function __construct(
        private MedicalInsuranceRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteMedicalInsurance($id);
    }
}
