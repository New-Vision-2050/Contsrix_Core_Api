<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Handlers;

use Modules\Company\RegistrationType\Repositories\RegistrationTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteRegistrationTypeHandler
{
    public function __construct(
        private RegistrationTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteRegistrationType($id);
    }
}
