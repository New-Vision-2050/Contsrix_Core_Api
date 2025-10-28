<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Services;

use Illuminate\Support\Collection;
use Modules\Shared\PaymentMethodData\DTO\CreatePaymentMethodDataDTO;
use Modules\Shared\PaymentMethodData\Models\PaymentMethodData;
use Modules\Shared\PaymentMethodData\Repositories\PaymentMethodDataRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class PaymentMethodDataCRUDService
{
    use HasExportService;

    public function __construct(
        private readonly PaymentMethodDataRepository $repository,
    ) {
    }

    public function create(CreatePaymentMethodDataDTO $createPaymentMethodDataDTO): PaymentMethodData
    {
         return $this->repository->createPaymentMethodData($createPaymentMethodDataDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): PaymentMethodData
    {
        return $this->repository->getPaymentMethodData(
            id: $id,
        );
    }

    public function getAll(): Collection
    {
        return PaymentMethodData::active()->get();
    }

    public function createMultiple(array $dtos): Collection
    {
        $createdMethods = collect();
        
        foreach ($dtos as $dto) {
            if ($dto instanceof CreatePaymentMethodDataDTO) {
                $method = PaymentMethodData::create($dto->toArray());
                $createdMethods->push($method);
            }
        }
        
        return $createdMethods;
    }

    public function getByType(string $type): ?PaymentMethodData
    {
        return PaymentMethodData::byType($type)->first();
    }
}
