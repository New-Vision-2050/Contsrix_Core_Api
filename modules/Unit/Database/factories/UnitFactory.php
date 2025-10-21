<?php

declare(strict_types=1);

namespace Modules\Unit\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Unit\Models\Unit;

/** @extends Factory<Unit> */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
