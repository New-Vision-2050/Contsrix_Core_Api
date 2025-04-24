<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Currency\Models\Currency;

/** @extends Factory<Currency> */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
