<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
