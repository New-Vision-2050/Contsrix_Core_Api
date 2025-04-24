<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Handlers;

use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteContactinfoHandler
{
    public function __construct(
        private ContactinfoRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteContactinfo($id);
    }
}
