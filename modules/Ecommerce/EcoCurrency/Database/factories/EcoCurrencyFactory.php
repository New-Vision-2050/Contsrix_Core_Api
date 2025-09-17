<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoCurrency\Models\EcoCurrency;

/** @extends Factory<EcoCurrency> */
class EcoCurrencyFactory extends Factory
{
    protected $model = EcoCurrency::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
