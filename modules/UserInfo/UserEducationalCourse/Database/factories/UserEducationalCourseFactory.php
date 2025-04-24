<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;

/** @extends Factory<UserEducationalCourse> */
class UserEducationalCourseFactory extends Factory
{
    protected $model = UserEducationalCourse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
