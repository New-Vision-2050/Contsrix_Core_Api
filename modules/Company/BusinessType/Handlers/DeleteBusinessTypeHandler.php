<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Handlers;

use Modules\Company\BusinessType\Repositories\BusinessTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBusinessTypeHandler
{
    public function __construct(
        private BusinessTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBusinessType($id);
    }
}
