<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserRelative\Models\UserRelative;

/** @extends Factory<UserRelative> */
class UserRelativeFactory extends Factory
{
    protected $model = UserRelative::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
