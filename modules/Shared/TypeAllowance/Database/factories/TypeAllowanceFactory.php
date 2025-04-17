<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;

/** @extends Factory<TypeAllowance> */
class TypeAllowanceFactory extends Factory
{
    protected $model = TypeAllowance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
