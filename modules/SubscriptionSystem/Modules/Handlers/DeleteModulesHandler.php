<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Handlers;

use Modules\SubscriptionSystem\Modules\Repositories\ModulesRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteModulesHandler
{
    public function __construct(
        private ModulesRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteModules($id);
    }
}
