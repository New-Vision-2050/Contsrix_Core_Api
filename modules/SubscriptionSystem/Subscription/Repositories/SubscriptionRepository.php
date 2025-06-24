<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubscriptionSystem\Subscription\Models\Subscription;

/**
 * @property Subscription $model
 * @method Subscription findOneOrFail($id)
 * @method Subscription findOneByOrFail(array $data)
 */
class SubscriptionRepository extends BaseRepository
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function getSubscriptionList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSubscription(UuidInterface $id): Subscription
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSubscription(array $data): Subscription
    {
        return $this->create($data);
    }

    public function updateSubscription(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSubscription(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
