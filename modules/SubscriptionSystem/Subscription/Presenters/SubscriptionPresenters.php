<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Presenters;

use Modules\SubscriptionSystem\Subscription\Models\Subscription;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SubscriptionPresenter extends AbstractPresenter
{
    private Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->subscription->id,
            'name' => $this->subscription->name,
        ];
    }
}
