<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Installment\DTO\CreateInstallmentDTO;
use Modules\Shared\Installment\Models\Installment;
use Modules\Shared\Installment\Repositories\InstallmentRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class InstallmentCRUDService
{
    use HasExportService;

    public function __construct(
        private InstallmentRepository $repository,
    ) {
    }

    public function create(CreateInstallmentDTO $createInstallmentDTO): Installment
    {
         return $this->repository->createInstallment($createInstallmentDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Installment
    {
        return $this->repository->getInstallment(
            id: $id,
        );
    }
}
