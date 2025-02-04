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
        ];
    }
}
