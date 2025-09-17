<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\EcoInstallment\DTO\CreateEcoInstallmentDTO;
use Modules\Ecommerce\EcoInstallment\DTO\UpsertEcoInstallmentDTO;
use Modules\Ecommerce\EcoInstallment\Models\EcoInstallment;
use Modules\Ecommerce\EcoInstallment\Repositories\EcoInstallmentRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoInstallmentCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoInstallmentRepository $repository,
    ) {
    }

    public function create(CreateEcoInstallmentDTO $createEcoInstallmentDTO): EcoInstallment
    {
         return $this->repository->createEcoInstallment($createEcoInstallmentDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoInstallment
    {
        return $this->repository->getEcoInstallment(
            id: $id,
        );
    }

    public function upsert(array $upsertDTOs): array
    {
        return DB::transaction(function () use ($upsertDTOs) {
            $results = [];
            $hasDefault = false;
            
            // First, check if any of the DTOs has is_default set to true
            foreach ($upsertDTOs as $dto) {
                if ($dto->isDefault) {
                    $hasDefault = true;
                    break;
                }
            }
            
            // If we have a default installment, reset all existing default installments
            if ($hasDefault && !empty($upsertDTOs)) {
                $this->repository->resetDefaultInstallments($upsertDTOs[0]->companyId);
            }
            
            foreach ($upsertDTOs as $dto) {
                $existing = $this->repository->findByCompanyAndInstallment(
                    $dto->companyId,
                    $dto->installmentId
                );
                
                if ($existing) {
                    // Update existing
                    $this->repository->updateEcoInstallment(
                        \Ramsey\Uuid\Uuid::fromString($existing->id),
                        $dto->toArray()
                    );
                    $results[] = $this->repository->getEcoInstallment(
                        \Ramsey\Uuid\Uuid::fromString($existing->id)
                    );
                } else {
                    // Create new
                    $results[] = $this->repository->createEcoInstallment($dto->toArray());
                }
            }
            
            return $results;
        });
    }
}
