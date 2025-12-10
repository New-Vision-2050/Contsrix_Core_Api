<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Handlers;

use Modules\WebsiteCMS\Founder\Repositories\FounderRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;

class DeleteFounderHandler
{
    public function __construct(
        private FounderRepository $repository,
        private WebsiteHomePageService $websiteHomePageService
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->websiteHomePageService->clearCache();
        $this->repository->deleteFounder($id);
    }
}
