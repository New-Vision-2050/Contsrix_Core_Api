<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\PaymentMethod\DTO\CreatePaymentMethodDTO;
use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;
use Modules\Ecommerce\PaymentMethod\Repositories\PaymentMethodRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class PaymentMethodCRUDService
{
    use HasExportService;

    public function __construct(
        private PaymentMethodRepository $repository,
    ) {
    }

    public function create(CreatePaymentMethodDTO $createPaymentMethodDTO): PaymentMethod
    {
         return $this->repository->createPaymentMethod($createPaymentMethodDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): PaymentMethod
    {
        return $this->repository->getPaymentMethod(
            id: $id,
        );
    }
}
