<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Handlers;

use Modules\WebsiteCMS\WebsiteContactInfo\Commands\UpdateWebsiteContactInfoCommand;
use Modules\WebsiteCMS\WebsiteContactInfo\Repositories\WebsiteContactInfoRepository;

class UpdateWebsiteContactInfoHandler
{
    public function __construct(
        private WebsiteContactInfoRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteContactInfoCommand $updateWebsiteContactInfoCommand)
    {
        $this->repository->updateWebsiteContactInfo($updateWebsiteContactInfoCommand->getId(), $updateWebsiteContactInfoCommand->toArray());
    }
}
