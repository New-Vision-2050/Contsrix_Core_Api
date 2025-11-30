<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Handlers;

use Modules\WebsiteCMS\WebsiteContactMessage\Commands\UpdateWebsiteContactMessageCommand;
use Modules\WebsiteCMS\WebsiteContactMessage\Repositories\WebsiteContactMessageRepository;

class UpdateWebsiteContactMessageHandler
{
    public function __construct(
        private WebsiteContactMessageRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteContactMessageCommand $updateWebsiteContactMessageCommand)
    {
        $this->repository->updateWebsiteContactMessage($updateWebsiteContactMessageCommand->getId(), $updateWebsiteContactMessageCommand->toArray());
    }
}
