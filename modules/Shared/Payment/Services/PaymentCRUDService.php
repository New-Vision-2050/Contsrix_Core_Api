<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Payment\DTO\CreatePaymentDTO;
use Modules\Shared\Payment\Models\Payment;
use Modules\Shared\Payment\Repositories\PaymentRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class PaymentCRUDService
{
    use HasExportService;

    public function __construct(
        private PaymentRepository $repository,
    ) {
    }

    public function create(CreatePaymentDTO $createPaymentDTO): Payment
    {
         return $this->repository->createPayment($createPaymentDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Payment
    {
        return $this->repository->getPayment(
            id: $id,
        );
    }
}
