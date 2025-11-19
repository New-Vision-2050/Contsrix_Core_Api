<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Handlers;

use Modules\WebsiteCMS\WebsiteProjectSetting\Repositories\WebsiteProjectSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteProjectSettingHandler
{
    public function __construct(
        private WebsiteProjectSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteProjectSetting($id);
    }
}
