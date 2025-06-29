<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Handlers;

use Modules\SubscriptionSystem\Subscription\Repositories\SubscriptionRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSubscriptionHandler
{
    public function __construct(
        private SubscriptionRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSubscription($id);
    }
}
