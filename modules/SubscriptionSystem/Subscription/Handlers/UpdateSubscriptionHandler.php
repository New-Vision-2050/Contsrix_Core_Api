<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Handlers;

use Modules\SubscriptionSystem\Subscription\Commands\UpdateSubscriptionCommand;
use Modules\SubscriptionSystem\Subscription\Repositories\SubscriptionRepository;

class UpdateSubscriptionHandler
{
    public function __construct(
        private SubscriptionRepository $repository,
    ) {
    }

    public function handle(UpdateSubscriptionCommand $updateSubscriptionCommand)
    {
        $this->repository->updateSubscription($updateSubscriptionCommand->getId(), $updateSubscriptionCommand->toArray());
    }
}
