<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;

/** @extends Factory<FeatureDeal> */
class FeatureDealFactory extends Factory
{
    protected $model = FeatureDeal::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
