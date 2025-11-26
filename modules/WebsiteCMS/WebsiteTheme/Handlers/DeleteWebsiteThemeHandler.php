<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Handlers;

use Modules\WebsiteCMS\WebsiteTheme\Repositories\WebsiteThemeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteThemeHandler
{
    public function __construct(
        private WebsiteThemeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteTheme($id);
    }
}
