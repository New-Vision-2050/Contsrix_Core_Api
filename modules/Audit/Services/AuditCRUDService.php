<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Support\Collection;
use Modules\Audit\Models\Audit;
use Modules\Audit\Repositories\AuditRepository;
use Ramsey\Uuid\UuidInterface;

class AuditCRUDService
{
    public function __construct(
        private AuditRepository $repository,
    ) {
    }


    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }


    public function groupedByDate (int $page = 1, int $perPage = 10)
    {
        return $this->repository->groupedBy();
    }

    public function get(UuidInterface $id): Audit
    {
        return $this->repository->getAudit(
            id: $id,
        );
    }
}
