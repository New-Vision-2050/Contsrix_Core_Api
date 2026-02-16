<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Services;

use Illuminate\Support\Collection;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceDTO;
use Modules\MedicalInsurance\Models\MedicalInsurance;
use Modules\MedicalInsurance\Repositories\MedicalInsuranceRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class MedicalInsuranceCRUDService
{
    use HasExportService;

    public function __construct(
        private MedicalInsuranceRepository $repository,
    ) {
    }

    public function create(CreateMedicalInsuranceDTO $createMedicalInsuranceDTO): MedicalInsurance
    {
         return $this->repository->createMedicalInsurance($createMedicalInsuranceDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): MedicalInsurance
    {
        return $this->repository->getMedicalInsurance(
            id: $id,
        );
    }
}
