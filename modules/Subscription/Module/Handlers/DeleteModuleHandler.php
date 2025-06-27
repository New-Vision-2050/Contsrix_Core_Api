<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Handlers;

use Modules\Subscription\Module\Repositories\ModuleRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteModuleHandler
{
    public function __construct(
        private ModuleRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteModule($id);
    }
}
