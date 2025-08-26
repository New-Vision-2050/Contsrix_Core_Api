<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

/** @extends Factory<EcoProduct> */
class EcoProductFactory extends Factory
{
    protected $model = EcoProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
