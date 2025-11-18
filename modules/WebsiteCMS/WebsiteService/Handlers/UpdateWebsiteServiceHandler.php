<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Handlers;

use Modules\WebsiteCMS\WebsiteService\Commands\UpdateWebsiteServiceCommand;
use Modules\WebsiteCMS\WebsiteService\Services\WebsiteServiceCRUDService;

class UpdateWebsiteServiceHandler
{
    public function __construct(
        private WebsiteServiceCRUDService $service
    ) {
    }

    public function handle(UpdateWebsiteServiceCommand $command)
    {
        return $this->service->update($command);
    }
}
