<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Handlers;

use Modules\UserInfo\Biography\Repositories\BiographyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBiographyHandler
{
    public function __construct(
        private BiographyRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBiography($id);
    }
}
