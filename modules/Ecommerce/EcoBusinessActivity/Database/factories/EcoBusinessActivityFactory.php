<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoBusinessActivity\Models\EcoBusinessActivity;

/** @extends Factory<EcoBusinessActivity> */
class EcoBusinessActivityFactory extends Factory
{
    protected $model = EcoBusinessActivity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
