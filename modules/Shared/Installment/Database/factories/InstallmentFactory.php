<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Installment\Models\Installment;

/** @extends Factory<Installment> */
class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
