<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Handlers;

use Modules\WebsiteCMS\WebsiteContactMessage\Repositories\WebsiteContactMessageRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteContactMessageHandler
{
    public function __construct(
        private WebsiteContactMessageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteContactMessage($id);
    }
}
