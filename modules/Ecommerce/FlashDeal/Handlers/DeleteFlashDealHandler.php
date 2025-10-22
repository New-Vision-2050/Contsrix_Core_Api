<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Handlers;

use Modules\Ecommerce\FlashDeal\Repositories\FlashDealRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFlashDealHandler
{
    public function __construct(
        private FlashDealRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFlashDeal($id);
    }
}
