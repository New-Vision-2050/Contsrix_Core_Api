<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoShop\Models\EcoShop;

/** @extends Factory<EcoShop> */
class EcoShopFactory extends Factory
{
    protected $model = EcoShop::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
