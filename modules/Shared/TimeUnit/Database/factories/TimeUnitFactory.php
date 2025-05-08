<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\TimeUnit\Models\TimeUnit;

/** @extends Factory<TimeUnit> */
class TimeUnitFactory extends Factory
{
    protected $model = TimeUnit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
