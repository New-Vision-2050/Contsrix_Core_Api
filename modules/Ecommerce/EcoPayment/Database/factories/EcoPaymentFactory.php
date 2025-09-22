<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoPayment\Models\EcoPayment;

/** @extends Factory<EcoPayment> */
class EcoPaymentFactory extends Factory
{
    protected $model = EcoPayment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
