<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\DealDay\Models\DealDay;

/** @extends Factory<DealDay> */
class DealDayFactory extends Factory
{
    protected $model = DealDay::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
