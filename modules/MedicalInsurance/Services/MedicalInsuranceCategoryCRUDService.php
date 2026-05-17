<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Services;

use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceCategoryDTO;
use Modules\MedicalInsurance\Models\MedicalInsuranceCategory;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class MedicalInsuranceCategoryCRUDService
{
    public function __construct(
        private MedicalInsuranceCategoryRepository $repository,
    ) {
    }

    public function list(string $medicalInsuranceId, int $page = 1, int $perPage = 10): array
    {
        return $this->repository->listByInsurance($medicalInsuranceId, $page, $perPage);
    }

    public function get(UuidInterface $id): MedicalInsuranceCategory
    {
        return $this->repository->getCategory($id);
    }

    public function create(CreateMedicalInsuranceCategoryDTO $dto): MedicalInsuranceCategory
    {
        return $this->repository->createCategory($dto->toArray());
    }

    public function update(UuidInterface $id, array $data): bool
    {
        return $this->repository->updateCategory($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteCategory($id);
    }
}
