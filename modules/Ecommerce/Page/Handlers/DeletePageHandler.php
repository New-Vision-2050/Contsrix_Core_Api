<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Handlers;

use Modules\Ecommerce\Page\Repositories\PageRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePageHandler
{
    public function __construct(
        private PageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePage($id);
    }
}
