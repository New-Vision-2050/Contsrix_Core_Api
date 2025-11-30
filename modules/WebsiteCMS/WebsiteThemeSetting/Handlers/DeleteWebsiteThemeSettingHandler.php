<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Handlers;

use Modules\WebsiteCMS\WebsiteThemeSetting\Repositories\WebsiteThemeSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteThemeSettingHandler
{
    public function __construct(
        private WebsiteThemeSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteThemeSetting($id);
    }
}
