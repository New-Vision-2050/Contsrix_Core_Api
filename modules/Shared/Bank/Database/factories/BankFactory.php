<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Bank\Models\Bank;

/** @extends Factory<Bank> */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
