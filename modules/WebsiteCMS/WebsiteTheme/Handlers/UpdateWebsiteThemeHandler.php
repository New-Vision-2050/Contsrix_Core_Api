<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Handlers;

use Modules\WebsiteCMS\WebsiteTheme\Commands\UpdateWebsiteThemeCommand;
use Modules\WebsiteCMS\WebsiteTheme\Repositories\WebsiteThemeRepository;

class UpdateWebsiteThemeHandler
{
    public function __construct(
        private WebsiteThemeRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteThemeCommand $updateWebsiteThemeCommand)
    {
        $this->repository->updateWebsiteTheme($updateWebsiteThemeCommand->getId(), $updateWebsiteThemeCommand->toArray());
    }
}
