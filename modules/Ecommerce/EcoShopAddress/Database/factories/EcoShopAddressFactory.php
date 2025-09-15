<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoShopAddress\Models\EcoShopAddress;

/** @extends Factory<EcoShopAddress> */
class EcoShopAddressFactory extends Factory
{
    protected $model = EcoShopAddress::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
