<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Handlers;

use Modules\Shared\Media\Commands\UpdateMediaCommand;
use Modules\Shared\Media\Repositories\MediaRepository;

class UpdateMediaHandler
{
    public function __construct(
        private MediaRepository $repository,
    ) {
    }

    public function handle(UpdateMediaCommand $updateMediaCommand)
    {
        $this->repository->updateMedia($updateMediaCommand->getId(), $updateMediaCommand->toArray());
    }
}
