<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

/** @extends Factory<UserProfessionalData> */
class UserProfessionalDataFactory extends Factory
{
    protected $model = UserProfessionalData::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
