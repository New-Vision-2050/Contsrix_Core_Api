<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;

/** @extends Factory<EcoOrderDetail> */
class EcoOrderDetailFactory extends Factory
{
    protected $model = EcoOrderDetail::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
