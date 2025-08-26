<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;

/** @extends Factory<EcoBrand> */
class EcoBrandFactory extends Factory
{
    protected $model = EcoBrand::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
