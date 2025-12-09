<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Handlers;

use Modules\WebsiteCMS\WebsiteIcon\Commands\UpdateWebsiteIconCommand;
use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;

class UpdateWebsiteIconHandler
{
    public function __construct(
        private WebsiteIconRepository $repository,
        private WebsiteHomePageService $homePageService,
    ) {
    }

    public function handle(UpdateWebsiteIconCommand $updateWebsiteIconCommand)
    {
        $result = $this->repository->updateWebsiteIcon(
            $updateWebsiteIconCommand->getId(),
            $updateWebsiteIconCommand->toArray(),
            $updateWebsiteIconCommand->getIcon()
        );
        
        $this->homePageService->clearCache();
        
        return $result;
    }
}
