<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserSalary\Models\UserSalary;

/** @extends Factory<UserSalary> */
class UserSalaryFactory extends Factory
{
    protected $model = UserSalary::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
