<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Home\Models\Home;

/** @extends Factory<Home> */
class HomeFactory extends Factory
{
    protected $model = Home::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
