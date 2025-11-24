<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Handlers;

use Modules\WebsiteCMS\WebsiteProject\Commands\UpdateWebsiteProjectCommand;
use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;

class UpdateWebsiteProjectHandler
{
    public function __construct(
        private WebsiteProjectRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteProjectCommand $updateWebsiteProjectCommand)
    {
        return $this->repository->updateWebsiteProject(
            id: $updateWebsiteProjectCommand->getId(),
            data: $updateWebsiteProjectCommand->toArray(),
            mainImage: $updateWebsiteProjectCommand->getMainImage(),
            secondaryImage: $updateWebsiteProjectCommand->getSecondaryImage(),
            projectDetails: $updateWebsiteProjectCommand->getProjectDetails(),
        );
    }
}
