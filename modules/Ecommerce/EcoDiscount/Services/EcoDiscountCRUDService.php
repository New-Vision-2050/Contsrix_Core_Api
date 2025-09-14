<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoDiscount\DTO\CreateEcoDiscountDTO;
use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;
use Modules\Ecommerce\EcoDiscount\Repositories\EcoDiscountRepository;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoDiscountCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoDiscountRepository $repository,
        private EcoProductRepository $ecoProductRepository,
    ) {
    }

    public function getProduct(UuidInterface $id): \Modules\Ecommerce\EcoProduct\Models\EcoProduct
    {
        return $this->ecoProductRepository->getEcoProduct($id);
    }

    public function create(CreateEcoDiscountDTO $createEcoDiscountDTO): EcoDiscount
    {
         return $this->repository->createEcoDiscount($createEcoDiscountDTO->toArray());
    }
    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            conditions: [],
            page: $page,
            perPage: $perPage,
        );
    }
    public function listProductDiscount(int $page = 1, int $perPage = 10): array
    {
        return $this->ecoProductRepository->paginated(
            conditions: ['has_discount' => true],
            page: $page,
            perPage: $perPage,
        );
    }


    public function get(UuidInterface $id): EcoDiscount
    {
        return $this->repository->getEcoDiscount($id);
    }

    public function getStatistics(): array
    {
        return $this->repository->getDiscountStatistics();
    }


    public function applyDiscount(string $code, float $orderAmount, array $productIds = []): array
    {
        return $this->repository->validateAndApplyDiscount($code, $orderAmount, $productIds);
    }
}
