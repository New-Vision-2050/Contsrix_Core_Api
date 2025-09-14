<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;

/** @extends Factory<EcoCategory> */
class EcoCategoryFactory extends Factory
{
    protected $model = EcoCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
