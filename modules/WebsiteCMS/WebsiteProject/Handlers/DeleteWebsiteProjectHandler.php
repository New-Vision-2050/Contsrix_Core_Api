<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Handlers;

use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteProjectHandler
{
    public function __construct(
        private WebsiteProjectRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteProject($id);
    }
}
