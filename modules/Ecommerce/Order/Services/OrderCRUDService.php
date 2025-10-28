<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Order\DTO\CreateOrderDTO;
use Modules\Ecommerce\Order\Models\Order;
use Modules\Ecommerce\Order\Repositories\OrderRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class OrderCRUDService
{
    use HasExportService;

    public function __construct(
        private OrderRepository $repository,
    ) {
    }

    public function create(CreateOrderDTO $createOrderDTO): Order
    {
         return $this->repository->createOrder($createOrderDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Order
    {
        return $this->repository->getOrder(
            id: $id,
        );
    }
}
