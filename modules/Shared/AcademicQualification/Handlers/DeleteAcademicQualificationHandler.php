<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Handlers;

use Modules\Shared\AcademicQualification\Repositories\AcademicQualificationRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteAcademicQualificationHandler
{
    public function __construct(
        private AcademicQualificationRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteAcademicQualification($id);
    }
}
