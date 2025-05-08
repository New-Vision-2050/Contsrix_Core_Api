<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Modules\SubEntity\Repositories\SuperEntityRepository;

class SuperEntityService
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function list(): array
    {
        return $this->repository->list();
    }

    public function getAvailableAttributes(string $superEntityName): array
    {
        return $this->repository->getAvailableAttributes($superEntityName);
    }
}
