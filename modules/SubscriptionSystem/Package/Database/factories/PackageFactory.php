<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubscriptionSystem\Package\Models\Package;

/** @extends Factory<Package> */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
