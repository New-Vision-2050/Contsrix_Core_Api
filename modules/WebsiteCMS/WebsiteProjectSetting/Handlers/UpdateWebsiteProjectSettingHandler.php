<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Handlers;

use Modules\WebsiteCMS\WebsiteProjectSetting\Commands\UpdateWebsiteProjectSettingCommand;
use Modules\WebsiteCMS\WebsiteProjectSetting\Repositories\WebsiteProjectSettingRepository;

class UpdateWebsiteProjectSettingHandler
{
    public function __construct(
        private WebsiteProjectSettingRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteProjectSettingCommand $updateWebsiteProjectSettingCommand)
    {
        $this->repository->updateWebsiteProjectSetting($updateWebsiteProjectSettingCommand->getId(), $updateWebsiteProjectSettingCommand->toArray());
    }
}
