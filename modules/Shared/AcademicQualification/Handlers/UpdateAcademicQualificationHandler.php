<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Handlers;

use Modules\Shared\AcademicQualification\Commands\UpdateAcademicQualificationCommand;
use Modules\Shared\AcademicQualification\Repositories\AcademicQualificationRepository;

class UpdateAcademicQualificationHandler
{
    public function __construct(
        private AcademicQualificationRepository $repository,
    ) {
    }

    public function handle(UpdateAcademicQualificationCommand $updateAcademicQualificationCommand)
    {
        $this->repository->updateAcademicQualification($updateAcademicQualificationCommand->getId(), $updateAcademicQualificationCommand->toArray());
    }
}
