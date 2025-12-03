<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Handlers;

use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use Modules\WebsiteCMS\WebsiteProject\Commands\UpdateWebsiteProjectCommand;
use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;

class UpdateWebsiteProjectHandler
{
    public function __construct(
        private WebsiteProjectRepository $repository,
        private WebsiteHomePageService $websiteHomePageService
    ) {
    }

    public function handle(UpdateWebsiteProjectCommand $updateWebsiteProjectCommand)
    {
        $this->websiteHomePageService->clearCache();
        return $this->repository->updateWebsiteProject(
            id: $updateWebsiteProjectCommand->getId(),
            data: $updateWebsiteProjectCommand->toArray(),
            mainImage: $updateWebsiteProjectCommand->getMainImage(),
            secondaryImages: $updateWebsiteProjectCommand->getSecondaryImages(),
            projectDetails: $updateWebsiteProjectCommand->getProjectDetails(),
        );
    }
}
