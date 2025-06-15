<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;

/** @extends Factory<TypeWorkingHour> */
class TypeWorkingHourFactory extends Factory
{
    protected $model = TypeWorkingHour::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
