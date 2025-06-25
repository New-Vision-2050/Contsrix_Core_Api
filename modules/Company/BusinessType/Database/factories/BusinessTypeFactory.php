<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\BusinessType\Models\BusinessType;

/** @extends Factory<BusinessType> */
class BusinessTypeFactory extends Factory
{
    protected $model = BusinessType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
