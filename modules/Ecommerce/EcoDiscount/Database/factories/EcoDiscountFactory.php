<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;

/** @extends Factory<EcoDiscount> */
class EcoDiscountFactory extends Factory
{
    protected $model = EcoDiscount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
