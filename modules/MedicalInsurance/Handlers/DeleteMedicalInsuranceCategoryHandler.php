<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Handlers;

use Modules\MedicalInsurance\Repositories\MedicalInsuranceCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMedicalInsuranceCategoryHandler
{
    public function __construct(
        private MedicalInsuranceCategoryRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id): void
    {
        $this->repository->deleteCategory($id);
    }
}
