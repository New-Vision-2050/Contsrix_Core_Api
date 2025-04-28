<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Handlers;

use Modules\CompanyUser\Repositories\MediaRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMediaHandler
{
    public function __construct(
        private MediaRepository $repository,
    ) {
    }

    public function handle($ids)
    {
        // If a single UUID is passed, make it an array
        $ids = is_array($ids) ? $ids : [$ids];

        $this->repository->delete($ids);
    }
}
