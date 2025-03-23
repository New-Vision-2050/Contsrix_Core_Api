<?php

declare(strict_types=1);

namespace Modules\Tenant\Handlers;

use Modules\Tenant\Repositories\TenantRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTenantHandler
{
    public function __construct(
        private TenantRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTenant($id);
    }
}
