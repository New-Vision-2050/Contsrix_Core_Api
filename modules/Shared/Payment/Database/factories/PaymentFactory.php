<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Payment\Models\Payment;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
