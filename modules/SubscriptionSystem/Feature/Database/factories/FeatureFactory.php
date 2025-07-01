<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubscriptionSystem\Feature\Models\Feature;

/** @extends Factory<Feature> */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
