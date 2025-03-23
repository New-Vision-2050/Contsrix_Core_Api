<?php

declare(strict_types=1);

namespace Modules\Tenant\Handlers;

use Modules\Tenant\Commands\UpdateTenantCommand;
use Modules\Tenant\Repositories\TenantRepository;

class UpdateTenantHandler
{
    public function __construct(
        private TenantRepository $repository,
    ) {
    }

    public function handle(UpdateTenantCommand $updateTenantCommand)
    {
        $this->repository->updateTenant($updateTenantCommand->getId(), $updateTenantCommand->toArray());
    }
}
