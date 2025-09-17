<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoInstallment\Models\EcoInstallment;

/** @extends Factory<EcoInstallment> */
class EcoInstallmentFactory extends Factory
{
    protected $model = EcoInstallment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
