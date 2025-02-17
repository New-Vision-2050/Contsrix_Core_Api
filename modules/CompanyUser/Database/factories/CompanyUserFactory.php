<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CompanyUser\Models\CompanyUser;

/** @extends Factory<CompanyUser> */
class CompanyUserFactory extends Factory
{
    protected $model = CompanyUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'residence' => $this->faker->optional()->randomNumber(8, true),
            'identity' => $this->faker->optional()->randomNumber(8, true),
            'passport' => $this->faker->optional()->randomNumber(8, true),
            'border_number' => $this->faker->optional()->randomNumber(8, true),
            'country_id' =>20,
        ];
    }
}
