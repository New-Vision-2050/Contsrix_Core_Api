<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubscriptionSystem\Subscription\Models\Subscription;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
