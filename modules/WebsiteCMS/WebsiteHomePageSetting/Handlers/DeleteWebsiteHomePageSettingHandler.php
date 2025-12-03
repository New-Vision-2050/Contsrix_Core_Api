<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Handlers;

use Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories\WebsiteHomePageSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteHomePageSettingHandler
{
    public function __construct(
        private WebsiteHomePageSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteHomePageSetting($id);
    }
}
