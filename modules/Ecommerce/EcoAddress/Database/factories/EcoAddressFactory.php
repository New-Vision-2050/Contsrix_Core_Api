<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;

/** @extends Factory<EcoAddress> */
class EcoAddressFactory extends Factory
{
    protected $model = EcoAddress::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
