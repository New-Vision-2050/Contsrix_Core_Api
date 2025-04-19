<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

/** @extends Factory<EmploymentContract> */
class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
