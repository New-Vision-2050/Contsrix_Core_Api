<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Handlers;

use Modules\WebsiteCMS\WebsiteOurService\Commands\UpdateWebsiteOurServiceCommand;
use Modules\WebsiteCMS\WebsiteOurService\Repositories\WebsiteOurServiceRepository;

class UpdateWebsiteOurServiceHandler
{
    public function __construct(
        private WebsiteOurServiceRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteOurServiceCommand $updateWebsiteOurServiceCommand)
    {
        return $this->repository->updateWebsiteOurService(
            $updateWebsiteOurServiceCommand->getId(),
            $updateWebsiteOurServiceCommand->toArray(),
            $updateWebsiteOurServiceCommand->getDepartments()
        );
    }
}
