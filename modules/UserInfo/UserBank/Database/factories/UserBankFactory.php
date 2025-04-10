<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserBank\Models\UserBank;

/** @extends Factory<UserBank> */
class UserBankFactory extends Factory
{
    protected $model = UserBank::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
