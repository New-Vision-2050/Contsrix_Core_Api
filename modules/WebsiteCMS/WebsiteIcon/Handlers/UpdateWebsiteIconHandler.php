<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Handlers;

use Modules\WebsiteCMS\WebsiteIcon\Commands\UpdateWebsiteIconCommand;
use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;

class UpdateWebsiteIconHandler
{
    public function __construct(
        private WebsiteIconRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteIconCommand $updateWebsiteIconCommand)
    {
        return $this->repository->updateWebsiteIcon(
            $updateWebsiteIconCommand->getId(),
            $updateWebsiteIconCommand->toArray(),
            $updateWebsiteIconCommand->getIcon()
        );
    }
}
