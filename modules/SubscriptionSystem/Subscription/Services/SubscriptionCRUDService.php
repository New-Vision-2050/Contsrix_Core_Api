<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Services;

use Illuminate\Support\Collection;
use Modules\SubscriptionSystem\Subscription\DTO\CreateSubscriptionDTO;
use Modules\SubscriptionSystem\Subscription\Models\Subscription;
use Modules\SubscriptionSystem\Subscription\Repositories\SubscriptionRepository;
use Ramsey\Uuid\UuidInterface;

class SubscriptionCRUDService
{
    public function __construct(
        private SubscriptionRepository $repository,
    ) {
    }

    public function create(CreateSubscriptionDTO $createSubscriptionDTO): Subscription
    {
         return $this->repository->createSubscription($createSubscriptionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Subscription
    {
        return $this->repository->getSubscription(
            id: $id,
        );
    }
}
