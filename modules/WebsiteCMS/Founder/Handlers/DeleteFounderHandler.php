<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Handlers;

use Modules\WebsiteCMS\Founder\Repositories\FounderRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFounderHandler
{
    public function __construct(
        private FounderRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFounder($id);
    }
}
