<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Period\Models\Period;

/** @extends Factory<Period> */
class PeriodFactory extends Factory
{
    protected $model = Period::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
