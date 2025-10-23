<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Handlers;

use Modules\Ecommerce\FlashDeal\Commands\UpdateFlashDealCommand;
use Modules\Ecommerce\FlashDeal\Repositories\FlashDealRepository;

class UpdateFlashDealHandler
{
    public function __construct(
        private FlashDealRepository $repository,
    ) {
    }

    public function handle(UpdateFlashDealCommand $updateFlashDealCommand)
    {
        $this->repository->updateFlashDeal($updateFlashDealCommand->getId(), $updateFlashDealCommand->toArray());
    }
}
