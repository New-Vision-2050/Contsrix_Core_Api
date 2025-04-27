<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\SalaryType\Models\SalaryType;

/** @extends Factory<SalaryType> */
class SalaryTypeFactory extends Factory
{
    protected $model = SalaryType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
