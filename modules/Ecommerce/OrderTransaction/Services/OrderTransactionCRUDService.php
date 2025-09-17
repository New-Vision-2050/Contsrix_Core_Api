<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\OrderTransaction\DTO\CreateOrderTransactionDTO;
use Modules\Ecommerce\OrderTransaction\Models\OrderTransaction;
use Modules\Ecommerce\OrderTransaction\Repositories\OrderTransactionRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class OrderTransactionCRUDService
{
    use HasExportService;

    public function __construct(
        private OrderTransactionRepository $repository,
    ) {
    }

    public function create(CreateOrderTransactionDTO $createOrderTransactionDTO): OrderTransaction
    {
         return $this->repository->createOrderTransaction($createOrderTransactionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): OrderTransaction
    {
        return $this->repository->getOrderTransaction(
            id: $id,
        );
    }
}
