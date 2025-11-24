<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Handlers;

use Modules\WebsiteCMS\WebsiteHomePageSetting\Commands\UpdateWebsiteHomePageSettingCommand;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories\WebsiteHomePageSettingRepository;

class UpdateWebsiteHomePageSettingHandler
{
    public function __construct(
        private WebsiteHomePageSettingRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteHomePageSettingCommand $updateWebsiteHomePageSettingCommand)
    {
        $this->repository->updateWebsiteHomePageSetting($updateWebsiteHomePageSettingCommand->getId(), $updateWebsiteHomePageSettingCommand->toArray());
    }
}
