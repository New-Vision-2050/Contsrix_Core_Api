<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Handlers;

use Modules\WebsiteCMS\Founder\Commands\UpdateFounderCommand;
use Modules\WebsiteCMS\Founder\Repositories\FounderRepository;
use Modules\WebsiteCMS\Founder\Models\Founder;

class UpdateFounderHandler
{
    public function __construct(
        private FounderRepository $repository,
    ) {
    }

    public function handle(UpdateFounderCommand $updateFounderCommand): Founder
    {
        return $this->repository->updateFounder(
            $updateFounderCommand->getId(),
            $updateFounderCommand->toArray(),
            $updateFounderCommand->getPersonalPhoto()
        );
    }
}
