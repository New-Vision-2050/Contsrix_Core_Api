<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Handlers;

use Modules\WebsiteCMS\WebsiteAboutUs\Repositories\WebsiteAboutUsRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteAboutUsHandler
{
    public function __construct(
        private WebsiteAboutUsRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteAboutUs($id);
    }
}
