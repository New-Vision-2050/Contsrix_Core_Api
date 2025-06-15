<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserAbout\Models\UserAbout;

/** @extends Factory<UserAbout> */
class UserAboutFactory extends Factory
{
    protected $model = UserAbout::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
