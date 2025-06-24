<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Handlers;

use Modules\Company\BusinessType\Commands\UpdateBusinessTypeCommand;
use Modules\Company\BusinessType\Repositories\BusinessTypeRepository;

class UpdateBusinessTypeHandler
{
    public function __construct(
        private BusinessTypeRepository $repository,
    ) {
    }

    public function handle(UpdateBusinessTypeCommand $updateBusinessTypeCommand)
    {
        $this->repository->updateBusinessType($updateBusinessTypeCommand->getId(), $updateBusinessTypeCommand->toArray());
    }
}
