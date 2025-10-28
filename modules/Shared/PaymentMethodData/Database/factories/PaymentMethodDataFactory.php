<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\PaymentMethodData\Models\PaymentMethodData;

/** @extends Factory<PaymentMethodData> */
class PaymentMethodDataFactory extends Factory
{
    protected $model = PaymentMethodData::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
