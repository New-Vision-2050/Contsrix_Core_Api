<?php

declare(strict_types=1);

namespace Modules\Country\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Country\Models\Country;

/** @extends Factory<Country> */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
