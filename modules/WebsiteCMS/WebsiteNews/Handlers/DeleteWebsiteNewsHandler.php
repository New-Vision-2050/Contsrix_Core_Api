<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Handlers;

use Modules\WebsiteCMS\WebsiteNews\Repositories\WebsiteNewsRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteNewsHandler
{
    public function __construct(
        private WebsiteNewsRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteNews($id);
    }
}
