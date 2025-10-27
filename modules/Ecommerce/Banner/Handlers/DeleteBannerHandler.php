<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Handlers;

use Modules\Ecommerce\Banner\Repositories\BannerRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBannerHandler
{
    public function __construct(
        private BannerRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBanner($id);
    }
}
