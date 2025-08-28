<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;

/** @extends Factory<EcoOrder> */
class EcoOrderFactory extends Factory
{
    protected $model = EcoOrder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
