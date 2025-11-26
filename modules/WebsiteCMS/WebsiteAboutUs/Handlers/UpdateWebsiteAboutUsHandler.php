<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Handlers;

use Modules\WebsiteCMS\WebsiteAboutUs\Commands\UpdateWebsiteAboutUsCommand;
use Modules\WebsiteCMS\WebsiteAboutUs\Repositories\WebsiteAboutUsRepository;

class UpdateWebsiteAboutUsHandler
{
    public function __construct(
        private WebsiteAboutUsRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteAboutUsCommand $updateWebsiteAboutUsCommand)
    {
        $this->repository->updateWebsiteAboutUs($updateWebsiteAboutUsCommand->getId(), $updateWebsiteAboutUsCommand->toArray());
    }
}
