<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserExperience\Models\UserExperience;

/** @extends Factory<UserExperience> */
class UserExperienceFactory extends Factory
{
    protected $model = UserExperience::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
