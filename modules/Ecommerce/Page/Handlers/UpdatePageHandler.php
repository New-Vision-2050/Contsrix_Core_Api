<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Handlers;

use Modules\Ecommerce\Page\Commands\UpdatePageCommand;
use Modules\Ecommerce\Page\Repositories\PageRepository;

class UpdatePageHandler
{
    public function __construct(
        private PageRepository $repository,
    ) {
    }

    public function handle(UpdatePageCommand $updatePageCommand)
    {
        $this->repository->updatePage($updatePageCommand->getId(), $updatePageCommand->toArray());
    }
}
