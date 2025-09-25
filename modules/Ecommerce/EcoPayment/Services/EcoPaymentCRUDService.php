<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\EcoPayment\DTO\CreateEcoPaymentDTO;
use Modules\Ecommerce\EcoPayment\DTO\UpsertEcoPaymentDTO;
use Modules\Ecommerce\EcoPayment\Models\EcoPayment;
use Modules\Ecommerce\EcoPayment\Repositories\EcoPaymentRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoPaymentCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoPaymentRepository $repository,
    ) {
    }

    public function create(CreateEcoPaymentDTO $createEcoPaymentDTO): EcoPayment
    {
         return $this->repository->createEcoPayment($createEcoPaymentDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoPayment
    {
        return $this->repository->getEcoPayment(
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
            
            // If we have a default payment, reset all existing default payments
            if ($hasDefault && !empty($upsertDTOs)) {
                $this->repository->resetDefaultPayments($upsertDTOs[0]->companyId);
            }
            
            foreach ($upsertDTOs as $dto) {
                $existing = $this->repository->findByCompanyAndPayment(
                    $dto->companyId,
                    $dto->paymentId
                );
                
                if ($existing) {
                    // Update existing
                    $this->repository->updateEcoPayment(
                        \Ramsey\Uuid\Uuid::fromString($existing->id),
                        $dto->toArray()
                    );
                    $results[] = $this->repository->getEcoPayment(
                        \Ramsey\Uuid\Uuid::fromString($existing->id)
                    );
                } else {
                    // Create new
                    $results[] = $this->repository->createEcoPayment($dto->toArray());
                }
            }
            
            return $results;
        });
    }
}
