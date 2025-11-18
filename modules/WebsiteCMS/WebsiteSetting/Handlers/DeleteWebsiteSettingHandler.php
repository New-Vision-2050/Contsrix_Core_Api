<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Handlers;

use Modules\WebsiteCMS\WebsiteSetting\Repositories\WebsiteSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteSettingHandler
{
    public function __construct(
        private WebsiteSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteSetting($id);
    }
}
