<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Handlers;

use Modules\WebsiteCMS\WebsiteThemeSetting\Commands\UpdateWebsiteThemeSettingCommand;
use Modules\WebsiteCMS\WebsiteThemeSetting\Repositories\WebsiteThemeSettingRepository;

class UpdateWebsiteThemeSettingHandler
{
    public function __construct(
        private WebsiteThemeSettingRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteThemeSettingCommand $updateWebsiteThemeSettingCommand)
    {
        $this->repository->updateWebsiteThemeSetting($updateWebsiteThemeSettingCommand->getId(), $updateWebsiteThemeSettingCommand->toArray());
    }
}
