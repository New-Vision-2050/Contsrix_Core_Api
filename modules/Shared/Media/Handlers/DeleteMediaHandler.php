<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Handlers;

use Modules\Shared\Media\Repositories\MediaRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteMediaHandler
{
    public function __construct(
        private MediaRepository $repository,
    ) {
    }

    public function handle($ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];

        return  $this->repository->delete($ids);
    }
}
