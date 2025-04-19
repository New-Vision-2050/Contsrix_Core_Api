<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Handlers;

use Modules\Shared\AcademicSpecialization\Repositories\AcademicSpecializationRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteAcademicSpecializationHandler
{
    public function __construct(
        private AcademicSpecializationRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteAcademicSpecialization($id);
    }
}
