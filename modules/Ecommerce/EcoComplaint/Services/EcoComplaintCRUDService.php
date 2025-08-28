<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoComplaint\DTO\CreateEcoComplaintDTO;
use Modules\Ecommerce\EcoComplaint\Models\EcoComplaint;
use Modules\Ecommerce\EcoComplaint\Repositories\EcoComplaintRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoComplaintCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoComplaintRepository $repository,
    ) {
    }

    public function create(CreateEcoComplaintDTO $createEcoComplaintDTO): EcoComplaint
    {
         return $this->repository->createEcoComplaint($createEcoComplaintDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoComplaint
    {
        return $this->repository->getEcoComplaint(
            id: $id,
        );
    }
}
