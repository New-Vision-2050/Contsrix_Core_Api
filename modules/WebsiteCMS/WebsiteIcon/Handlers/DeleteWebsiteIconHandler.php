<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Handlers;

use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteIconHandler
{
    public function __construct(
        private WebsiteIconRepository $repository,
        private WebsiteHomePageService $homePageService,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteIcon($id);
        
        $this->homePageService->clearCache();
    }
}
