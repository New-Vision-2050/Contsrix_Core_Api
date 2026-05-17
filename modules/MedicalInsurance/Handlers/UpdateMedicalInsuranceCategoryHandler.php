<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceCategoryCommand;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceCategoryRepository;

class UpdateMedicalInsuranceCategoryHandler
{
    public function __construct(
        private MedicalInsuranceCategoryRepository $repository,
    ) {
    }

    public function handle(UpdateMedicalInsuranceCategoryCommand $command): void
    {
        $this->repository->updateCategory($command->getId(), $command->toArray());
    }
}
