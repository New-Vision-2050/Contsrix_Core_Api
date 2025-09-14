<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Warehous\Models\Warehous;

/** @extends Factory<Warehous> */
class WarehousFactory extends Factory
{
    protected $model = Warehous::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
