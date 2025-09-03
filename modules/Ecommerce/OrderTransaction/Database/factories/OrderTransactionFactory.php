<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\OrderTransaction\Models\OrderTransaction;

/** @extends Factory<OrderTransaction> */
class OrderTransactionFactory extends Factory
{
    protected $model = OrderTransaction::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
