<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceCommand;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceRepository;

class UpdateMedicalInsuranceHandler
{
    public function __construct(
        private MedicalInsuranceRepository $repository,
    ) {
    }

    public function handle(UpdateMedicalInsuranceCommand $updateMedicalInsuranceCommand)
    {
        $this->repository->updateMedicalInsurance($updateMedicalInsuranceCommand->getId(), $updateMedicalInsuranceCommand->toArray());
    }
}
