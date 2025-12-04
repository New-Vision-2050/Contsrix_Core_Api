<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Handlers;

use Modules\WebsiteCMS\WebsiteHomePage\Repositories\WebsiteHomePageRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteHomePageHandler
{
    public function __construct(
        private WebsiteHomePageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteHomePage($id);
    }
}
