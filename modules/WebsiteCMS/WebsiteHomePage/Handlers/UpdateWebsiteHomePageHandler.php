<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Handlers;

use Modules\WebsiteCMS\WebsiteHomePage\Commands\UpdateWebsiteHomePageCommand;
use Modules\WebsiteCMS\WebsiteHomePage\Repositories\WebsiteHomePageRepository;

class UpdateWebsiteHomePageHandler
{
    public function __construct(
        private WebsiteHomePageRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteHomePageCommand $updateWebsiteHomePageCommand)
    {
        $this->repository->updateWebsiteHomePage($updateWebsiteHomePageCommand->getId(), $updateWebsiteHomePageCommand->toArray());
    }
}
