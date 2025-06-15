<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Handlers;

use Modules\Shared\AcademicSpecialization\Commands\UpdateAcademicSpecializationCommand;
use Modules\Shared\AcademicSpecialization\Repositories\AcademicSpecializationRepository;

class UpdateAcademicSpecializationHandler
{
    public function __construct(
        private AcademicSpecializationRepository $repository,
    ) {
    }

    public function handle(UpdateAcademicSpecializationCommand $updateAcademicSpecializationCommand)
    {
        $this->repository->updateAcademicSpecialization($updateAcademicSpecializationCommand->getId(), $updateAcademicSpecializationCommand->toArray());
    }
}
