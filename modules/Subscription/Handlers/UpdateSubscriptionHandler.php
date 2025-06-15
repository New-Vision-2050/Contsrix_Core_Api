<?php

declare(strict_types=1);

namespace Modules\Subscription\Handlers;

use Modules\Subscription\Commands\UpdateSubscriptionCommand;
use Modules\Subscription\Repositories\SubscriptionRepository;

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
