<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Handlers;

use Modules\WebsiteCMS\WebsiteNews\Commands\UpdateWebsiteNewsCommand;
use Modules\WebsiteCMS\WebsiteNews\Repositories\WebsiteNewsRepository;
use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;

class UpdateWebsiteNewsHandler
{
    public function __construct(
        private WebsiteNewsRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteNewsCommand $updateWebsiteNewsCommand): WebsiteNews
    {
        return $this->repository->updateWebsiteNews(
            $updateWebsiteNewsCommand->getId(),
            $updateWebsiteNewsCommand->toArray(),
            $updateWebsiteNewsCommand->getMainImage(),
            $updateWebsiteNewsCommand->getThumbnail()
        );
    }
}
